<?php

namespace MakinaCorpus\DbToolsBundle\Command;

use MakinaCorpus\DbToolsBundle\Backupper\BackupperFactoryRegistry;
use MakinaCorpus\DbToolsBundle\Restorer\RestorerFactoryRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'db-tools:check')]
class CheckCommand extends Command
{
    public function __construct(
        private string $defaultConnectionName,
        private BackupperFactoryRegistry $backupperFactoryRegistry,
        private RestorerFactoryRegistry $restorerFactoryRegistry,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Check DbTools configuration')
            ->addArgument(
                'connection',
                InputArgument::OPTIONAL,
                'A doctrine connection name. If not given, use default connection'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $connection = $input->getArgument('connection') ?? $this->defaultConnectionName;

        $backupper = $this->backupperFactoryRegistry->create($connection);
        $response = $backupper->checkBinary();
        $io->success("Backupper binary ok : " . $response);

        $restorer = $this->restorerFactoryRegistry->create($connection);
        $response = $restorer->checkBinary();
        $io->success("Restorer binary ok : " . $response);

        return Command::SUCCESS;
    }
}