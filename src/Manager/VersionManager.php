<?php

namespace Dbtlr\MigrationProvider\Manager;

use Dbtlr\MigrationProvider\Migration\AbstractMigration;
use Doctrine\DBAL\Connection;
use Silex\Application;
use Symfony\Component\Finder\Finder;

class VersionManager extends AbstractManager
{
    /** @var Finder */
    protected $finder;

    /** @var string */
    protected $migrationsPath;

    /**
     * @param Connection $connection
     * @param Application $application
     * @param Finder $finder
     */
    public function __construct(Connection $connection, Application $application, Finder $finder)
    {
        $this->finder = $finder;

        $this->migrationsPath = $application['migrations.path'];

        parent::__construct($connection, $application);
    }

    /**
     * @param string|null $name
     * @return string
     */
    public function createNewVersion($name = null)
    {
        if (!is_writable($this->migrationsPath)) {
            throw new \RuntimeException(
                sprintf('Cannot create a migration version in %s, path is not writable.', $this->migrationsPath)
            );
        }

        $version     = time();
        $className   = 'Version' . $version . $this->camelCase($name) . 'Migration';
        $description = 'Version ' . $version . ($name ? ' - ' . $name : '');

        $template = $this->getMigrationTemplate($className, $description);

        $filename = $this->migrationsPath . '/' . $className . '.php';

        file_put_contents($filename, $template);

        return $filename;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function camelCase($name)
    {
        return str_replace(' ', '', ucwords(strtolower($name)));
    }

    /**
     * @param string $className
     * @param string $description
     * @return string
     */
    protected function getMigrationTemplate($className, $description = '')
    {
        $templateFile = realpath(__DIR__ . '/../../views/Migration.php.dist');

        $template = file_get_contents($templateFile);

        return sprintf($template, $className, $description);
    }

    /**
     * @return int
     */
    public function runMigrations()
    {
        $allVersions = $this->getAllMigrationVersions();
        $registeredVersions = $this->getPreviouslyRunVersions();

        $this->warnAboutUnknownMigrationsInDatabase($registeredVersions, array_keys($allVersions));

        $toRun = $this->limitToMigrationsToRun($allVersions, $registeredVersions);

        if (count($toRun) == 0) {
            return 0;
        }

        $this->writeln(sprintf('<info>Found %d versions to migrate.</info>', count($toRun)));

        foreach ($toRun as $migration) {
            $this->processMigration($migration['class'], $migration['version'], $migration['filePath']);
        }

        return count($toRun);
    }

    /**
     * @return array
     */
    protected function getAllMigrationVersions()
    {
        $versions = array();

        $finder = clone($this->finder);
        $finder->files()->name('Version*Migration.php')->sortByName();

        foreach ($finder as $migration) {
            /** @var \Symfony\Component\Finder\SplFileInfo $migration */
            $fileName = $migration->getFilename();
            $filePath = $migration->getRealPath();

            if (!preg_match('/^(Version(\d+)(.*)Migration).php$/', $fileName, $matches)) {
                continue;
            }

            $className = $matches[1];
            $version   = $matches[2];
            $name      = $matches[3];

            $versions[$version] = array(
                'class'    => $className,
                'version'  => $version,
                'name'     => $name,
                'filePath' => $filePath,
            );
        }

        return $versions;
    }

    /**
     * @return array
     */
    protected function getPreviouslyRunVersions()
    {
        $results = $this->connection->fetchAll('SELECT version FROM ' . $this->migrationsTableName);

        $versions = array_map(function ($item) {
            return $item['version'];
        }, $results);

        return $versions;
    }

    /**
     * @param array $registered
     * @param array $all
     */
    protected function warnAboutUnknownMigrationsInDatabase(array $registered, array $all)
    {
        $diff = array_diff($registered, $all);

        if (count($diff) > 0) {
            $this->writeln('<question>Found unknown migrations in the database.</question>');

            foreach ($diff as $version) {
                $this->writeln(' - ' . $version);
            }

            $this->writeln('');
        }
    }

    /**
     * @param array $all
     * @param array $registered
     * @return array
     */
    protected function limitToMigrationsToRun(array $all, array $registered)
    {
        $versions = array();

        $toRunVersions = array_diff(array_keys($all), $registered);

        foreach ($toRunVersions as $version) {
            $versions[$version] = $all[$version];
        }

        return $versions;
    }

    /**
     * @param string $className
     * @param int $version
     * @param string $filePath
     */
    protected function processMigration($className, $version, $filePath)
    {
        $migration = $this->loadMigration($filePath, $className);

        $schema = clone $this->getSchema();

        $migration->up($schema);

        $this->writeln('Running migration: ' . $migration->getMigrationInfo());

        $this->processSchema($schema);
        $this->saveMigrationVersion($version);
    }

    /**
     * @param string $file
     * @param string $class
     * @return AbstractMigration
     */
    protected function loadMigration($file, $class)
    {
        require_once $file;

        $fullClassName = '\\Migration\\'.$class;

        if (!class_exists($fullClassName)) {
            throw new \RuntimeException(sprintf('Could not find class "%s" in "%s"', $fullClassName, $file));
        }

        /** @var \Dbtlr\MigrationProvider\Migration\AbstractMigration $migration */
        $migration = new $fullClassName();

        if (!$migration instanceof AbstractMigration) {
            throw new \RuntimeException(
                sprintf('Migration found in %s was not a valid AbstractMigration as expected', $file)
            );
        }

        $migration->setApplication($this->application);

        return $migration;
    }

    /**
     * @param int $version
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function saveMigrationVersion($version)
    {
        $stmt = $this->connection->prepare('INSERT INTO ' . $this->getTableName() . ' (version) VALUES (:version)');
        $stmt->bindParam('version', $version);
        $stmt->execute();
    }
}
