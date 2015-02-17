<?php

namespace Dbtlr\MigrationProvider\Command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{
    /**
     * Configure the migration command.
     */
    public function configure()
    {
        $this->setName('migrations:migrate')
            ->setDescription('Find all un-migrated versions and run them now.')
            ->addArgument('direction', InputArgument::OPTIONAL, 'Which direction should you migrate [up/down]', 'up')
            ->addArgument('version', InputArgument::OPTIONAL, 'Which version to migrate to', null);

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $direction = strtolower($input->getArgument('direction'));
        $version = $input->getArgument('version');

        if ($direction != 'up' && $direction != 'down') {
            throw new \InvalidArgumentException('direction must be either `up` or `down`, ' . $direction . ' given');
        }

        if ($direction == 'down' && !$version) {
            throw new \InvalidArgumentException('With a `down` direction, a version is required.');
        }

        $app = $this->getSilexApplication();

        /** @var \Dbtlr\MigrationProvider\Manager\TableManager $tableManager */
        $tableManager = $app['migrations.manager.table'];
        $tableManager->setOutput($output);
        $tableManager->createVersionTable();

        /** @var \Dbtlr\MigrationProvider\Manager\VersionManager $manager */
        $manager = $app['migrations.manager.version'];
        $manager->setOutput($output);

        $count = $manager->runMigrations($direction, $version);

        if ($count == 0) {
            $output->writeln('No migrations to execute, you are up to date!');
            return;
        }

        $output->writeln(
            sprintf('Succesfully executed <info>%d</info> migration(s)!', $count)
        );
    }
}
