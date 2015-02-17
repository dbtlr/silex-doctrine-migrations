<?php

namespace Dbtlr\MigrationProvider\Migration;

use Doctrine\DBAL\Schema\Schema;
use Silex\Application;

abstract class AbstractMigration
{
    /** @var Application */
    protected $application;

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param Application $application
     */
    public function setApplication(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        $class = get_class($this);
        $pieces = explode('\\', $class);

        $className = array_pop($pieces);

        if (!preg_match('/^Version([\d]{10})([a-zA-Z]*)Migration$/', $className, $matches)) {
            throw new \RuntimeException('Could not find version for misnamed migration class ' . $className);
        }

        $version = $matches[1];

        return $version;
    }

    /**
     * @return null
     */
    public function getMigrationInfo()
    {
        return 'Version ' . $this->getVersion();
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
