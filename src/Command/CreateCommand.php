<?php

namespace Dbtlr\MigrationProvider\Command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends Command
{
    /**
     * configure the migration command.
     */
    public function configure()
    {
        $this->setName('migrations:create')
            ->setDescription('Create a new migration')
            ->addArgument('name', InputArgument::OPTIONAL, 'Optional name for the migration.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        $app = $this->getSilexApplication();

        /** @var \Dbtlr\MigrationProvider\Manager\TableManager $tableManager */
        $tableManager = $app['migrations.manager.table'];
        $tableManager->setOutput($output);
        $tableManager->createVersionTable();

        /** @var \Dbtlr\MigrationProvider\Manager\VersionManager $versionManager */
        $versionManager = $app['migrations.manager.version'];
        $versionManager->setOutput($output);
        $filename = $versionManager->createNewVersion($name);

        $output->writeln(sprintf('<info>A new migration has been successfully created in: %s</info>', $filename));
        return 0;
    }
}
