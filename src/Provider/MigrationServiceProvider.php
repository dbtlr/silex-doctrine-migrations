<?php

namespace Dbtlr\MigrationProvider\Provider;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tools\Console\Command;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Silex\ServiceProviderInterface;
use Silex\Application;
use Ivoba\Silex\Console\ConsoleEvents;
use Ivoba\Silex\Console\ConsoleEvent;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\EventDispatcher\EventDispatcher;

class MigrationServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['db.migrations.namespace'] = 'DoctrineMigrations';
        $app['db.migrations.path'] = null;
        $app['db.migrations.table_name'] = null;
        $app['db.migrations.name'] = null;

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addListener(ConsoleEvents::INIT, function (ConsoleEvent $event) use ($app) {
            $application = $event->getApplication();

            $helpers = array('dialog' => new QuestionHelper());

            if (isset($app['orm.em'])) {
                $helpers['em'] = new EntityManagerHelper($app['orm.em']);
            }

            $helperSet = new HelperSet($helpers);

            $application->setHelperSet($helperSet);

            $config = new Configuration($app['db']);
            $config->setMigrationsNamespace($app['db.migrations.namespace']);

            if ($app['db.migrations.path']) {
                $config->setMigrationsDirectory($app['db.migrations.path']);
                $config->registerMigrationsFromDirectory($app['db.migrations.path']);
            }

            if ($app['db.migrations.name']) {
                $config->setName($app['db.migrations.name']);
            }

            if ($app['db.migrations.table_name']) {
                $config->setMigrationsTableName($app['db.migrations.table_name']);
            }

            $commands = array(
                new Command\ExecuteCommand(),
                new Command\GenerateCommand(),
                new Command\MigrateCommand(),
                new Command\StatusCommand(),
                new Command\VersionCommand(),
            );

            if (isset($app['orm.em'])) {
                $commands[] = new Command\DiffCommand();
            }

            foreach ($commands as $command) {
                /** @var \Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand $command */
                $command->setMigrationConfiguration($config);
                $application->add($command);
            }
        });
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {

    }
}
