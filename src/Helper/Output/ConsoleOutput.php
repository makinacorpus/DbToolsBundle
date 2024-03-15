<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper\Output;

use Symfony\Component\Console\Output\OutputInterface as SymfonyOutputInterface;

class ConsoleOutput extends AbstractOutput
{
    public function __construct(
        private SymfonyOutputInterface $output,
        int $indentSize = 2,
    ) {
        parent::__construct($indentSize);
    }

    #[\Override]
    protected function doWrite(string $text = '', array $values = [], int $lineBreak = 0): void
    {
        if ($text) {
            if ($values) {
                $text = \sprintf($text, ...$values);
            }
            if ($this->indentCount) {
                $lines = \preg_split('/\R/', $text);
                $indentation = \str_repeat(' ', $this->indentCount * $this->indentSize);
                foreach ($lines as $index => $line) {
                    $newLine = $index < (\count($lines) - 1);
                    $this->output->write($indentation . $line, $newLine);
                }
            }
            else {
                $this->output->write($text);
            }
        }
        if ($lineBreak > 0) {
            $this->output->write(\str_repeat(\PHP_EOL, $lineBreak));
        }
    }
}
