<?php

namespace Dbtlr\MigrationProvider\Manager;

use Silex\Application;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractManager
{
    /** @var OutputInterface */
    protected $output;

    /** @var Application */
    protected $application;

    /** @var Connection */
    protected $connection;

    /** @var string */
    protected $migrationsTableName = 'migration_versions';

    /**
     * @param Connection $connection
     * @param Application $application
     */
    public function __construct(Connection $connection, Application $application)
    {
        $this->connection  = $connection;
        $this->application = $application;

        if (isset($application['migration.table_name'])) {
            $this->migrationsTableName = $application['migration.table_name'];
        }
    }

    /**
     * @return mixed|string
     */
    public function getTableName()
    {
        return $this->migrationsTableName;
    }

    /**
     * @return Schema
     */
    public function getSchema()
    {
        return $this->connection->getSchemaManager()->createSchema();
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * If a console output is available, this will write out to it.
     *
     * @param string $message
     * @param int $type
     */
    protected function writeln($message, $type = OutputInterface::OUTPUT_NORMAL)
    {
        if ($this->output) {
            $this->output->writeln($message, $type);
        }
    }

    /**
     * Processes the given schema against the global
     * schema, running the diff set.
     *
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function processSchema(Schema $schema)
    {
        $queries = $this->getSchema()->getMigrateToSql($schema, $this->connection->getDatabasePlatform());

        foreach ($queries as $query) {
            $this->writeln('Executing: ' . $query);
            $this->connection->exec($query);
        }
    }
}
