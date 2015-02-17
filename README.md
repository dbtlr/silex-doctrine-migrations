# Silex Migration Provider

Quick Doctrine migration service provider, somewhat based on the KnpLabs version, though I've largely rewritten it to be more feature full.

## Install via Composer

```
composer.phar require dbtlr/silex-doctrine-migrations
```

## Add service provider

```php
$app->register(new \Dbtlr\MigrationProvider\MigrationServiceProvider(), array(
    'migrations.path' => __DIR__ . '/../app/migrations'
));
```

### Config options

- `migrations.path` (required): The full path where you want to store your migration classes.
- `migrations.table_name` (optional): The name of the table that we store meta information about the state of migrations. Defaults to: migration_versions.


## Available commands

- migrations:reset - Be careful with this one, it will blow up the versions table and start over. You probably don't want to do this.
- migrations:create - Create a new migration class in your migration path.
- migrations:migrate - Run migrations and come to current.

## Example migration class

```php
<?php

namespace Migration;

use Dbtlr\MigrationProvider\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version0123456789Migration extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Your schema up migration
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // Your schema down migration.
    }

    /**
     * @return string
     */
    public function getMigrationInfo()
    {
        return 'Some plain text telling you what this does.';
    }
}

```

For more information on how to use the schema manager, please see [Doctrine's Schema Manager documentation](http://readthedocs.org/docs/doctrine-dbal/en/latest/reference/schema-manager.html). 

```

## Todo

There are still some missing pieces.

- The `up`/`down` commands for migrations need to be added.
- The ability to target a version to migrate to.