<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Command;

use MakinaCorpus\DbToolsBundle\Backupper\BackupperFactory;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\DbToolsBundle\Restorer\RestorerFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'db-tools:check', description: 'Check DbTools configuration')]
class CheckCommand extends Command
{
    public function __construct(
        private string $defaultConnectionName,
        private BackupperFactory $backupperFactory,
        private RestorerFactory $restorerFactory,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
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

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $connection = $input->getArgument('connection') ?? $this->defaultConnectionName;

        try {
            $backupper = $this->backupperFactory->create($connection);
            $response = $backupper->checkBinary();
            $io->success("Backupper binary ok : " . $response);

            $restorer = $this->restorerFactory->create($connection);
            $response = $restorer->checkBinary();
            $io->success("Restorer binary ok : " . $response);
        } catch (NotImplementedException $e) {
            $io->error($e->getMessage());

            return NotImplementedException::CONSOLE_EXIT_STATUS;
        }

        return Command::SUCCESS;
    }
}
