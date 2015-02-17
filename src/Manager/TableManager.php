<?php

namespace Dbtlr\MigrationProvider\Manager;

class TableManager extends AbstractManager
{
    /**
     * Create the version table and index.
     */
    public function createVersionTable()
    {
        $schema = clone $this->getSchema();

        if (!$schema->hasTable($this->getTableName())) {
            $this->writeln('Creating version table...');
            $schemaVersion = $schema->createTable($this->getTableName());
            $schemaVersion->addColumn('version', 'string');
            $schemaVersion->setPrimaryKey(array('version'));

            $this->processSchema($schema);
        }
    }

    /**
     * Drop the version table, destroying it and its contents.
     */
    public function dropVersionTable()
    {
        $schema = clone $this->getSchema();

        if ($schema->hasTable($this->getTableName())) {
            $this->writeln('Dropping version table...');
            $schema->dropTable($this->getTableName());
            $this->processSchema($schema);
        }
    }

    /**
     * Reset the version table completely.
     */
    public function reset()
    {
        $this->dropVersionTable();
        $this->createVersionTable();
    }
}
