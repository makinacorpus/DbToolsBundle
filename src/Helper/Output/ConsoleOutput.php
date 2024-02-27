<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper\Output;

use Symfony\Component\Console\Output\OutputInterface as SymfonyOutputInterface;

class ConsoleOutput implements OutputInterface
{
    private int $indentCount = 0;

    public function __construct(
        private SymfonyOutputInterface $output,
        private int $indentSize = 4,
    ) {}

    public function write(string $text) : void
    {
        $this->doWrite($text);
    }

    public function writeLine(string $text, int $lineBreak = 1) : void
    {
        $this->doWrite($text, $lineBreak);
    }

    public function newLine(int $count = 1) : void
    {
        $this->doWrite(null, $count);
    }

    public function indent(int $count = 1) : void
    {
        $this->indentCount += $count;
    }

    public function outdent(int $count = 1) : void
    {
        $this->indentCount = \max(0, $this->indentCount - $count);
    }

    private function doWrite(?string $text, int $lineBreak = 0): void
    {
        if ($text) {
            $lines = \preg_split('/\R/', $text);
            $indentation = \str_repeat(' ', $this->indentCount * $this->indentSize);
            foreach ($lines as $index => $line) {
                $newLine = $index < (\count($lines) - 1);
                $this->output->write($indentation . $line, $newLine);
            }
        }
        if ($lineBreak > 0) {
            $this->output->write(\str_repeat(\PHP_EOL, $lineBreak));
        }
    }
}
