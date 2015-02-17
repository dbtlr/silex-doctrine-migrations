<?php

namespace Dbtlr\MigrationProvider\Provider;

use Dbtlr\MigrationProvider\Command\CreateCommand;
use Dbtlr\MigrationProvider\Command\ResetCommand;
use Dbtlr\MigrationProvider\Manager\TableManager;
use Dbtlr\MigrationProvider\Manager\VersionManager;
use Dbtlr\MigrationProvider\Command\MigrateCommand;
use Symfony\Component\Finder\Finder;
use Silex\ServiceProviderInterface;
use Silex\Application;
use Knp\Console\ConsoleEvents;
use Knp\Console\ConsoleEvent;

class MigrationServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['migrations.manager.version'] = $app->share(function () use ($app) {
            if (!$app->offsetExists('migrations.path')) {
                throw new \RuntimeException('The migrations.path must be provided in order to manage versions.');
            }

            return new VersionManager($app['db'], $app, Finder::create()->in($app['migrations.path']));
        });

        $app['migrations.manager.table'] = $app->share(function () use ($app) {
            return new TableManager($app['db'], $app);
        });

        $app['dispatcher']->addListener(ConsoleEvents::INIT, function (ConsoleEvent $event) {
            $application = $event->getApplication();
            $application->add(new MigrateCommand());
            $application->add(new CreateCommand());
            $application->add(new ResetCommand());
        });
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {

    }
}
