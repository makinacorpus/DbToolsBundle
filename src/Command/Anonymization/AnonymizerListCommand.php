<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Command\Anonymization;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'db-tools:anonymization:list', description: 'List all available anonymizers')]
class AnonymizerListCommand extends Command
{
    public function __construct(
        private AnonymizerRegistry $anonymizerRegistry,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

        $io = new SymfonyStyle($input, $output);

        $rawList = $this->anonymizerRegistry->getAllAnonymizerMetadata();

        $list = [];
        foreach ($rawList as $metadata) {
            \assert($metadata instanceof AsAnonymizer);

            if (!\array_key_exists($metadata->pack, $list)) {
                $list[$metadata->pack] = [];
            }

            $description = $metadata->description;
            if ($metadata->missingRequirements()) {
                $description .= "\n" . \sprintf('<error>Dependencies are missing: "%s"</error>', \implode('", "', $metadata->dependencies));
            }

            $list[$metadata->pack]['<info>' . $metadata->id() . '</info>'] = $description;
        }

        \array_walk($list, fn (array &$anonymizers) => \ksort($anonymizers, SORT_STRING));

        $table = [];
        $first = true;
        foreach ($list as $pack => $anonymizers) {
            if (!$first) {
                $table[] = new TableSeparator();
            } else {
                $first = false;
            }
            $table[] = ['<comment>'.$pack.'</comment>', ''];
            $table[] = new TableSeparator();
            foreach ($anonymizers as $name => $description) {
                $table[] = [$name, $description];
            }
        }

        $io->table([], $table);

        return Command::SUCCESS;
    }
}
