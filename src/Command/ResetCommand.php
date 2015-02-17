<?php

namespace Dbtlr\MigrationProvider\Command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResetCommand extends Command
{
    /**
     * configure the migration command.
     */
    public function configure()
    {
        $this->setName('migrations:reset')
            ->setDescription('Reset the version information database')
            ->addOption('force');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$force = $input->getOption('force')) {
            $output->writeln(
                '<error>Danger, this will destroy your version history. Never ever do this in a ' .
                'production environment, as you will have to manually set you versions after you do this.</error>'
            );

            throw new \RuntimeException('Add the --force command if you are certain you wish to do this.');
        }

        $app = $this->getSilexApplication();

        /** @var \Dbtlr\MigrationProvider\Manager\TableManager $manager */
        $manager = $app['migrations.manager.table'];
        $manager->setOutput($output);

        $manager->reset();

        $output->writeln('');
        $output->writeln('<info>Your version history has been successfully reset.</info>');
        return 0;
    }
}
