<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper\Output;

abstract class AbstractOutput implements OutputInterface
{
    protected int $indentCount = 0;

    public function __construct(
        protected int $indentSize = 2
    ) {}

    public function write(string $text, mixed ...$values): void
    {
        $this->doWrite($text, $values);
    }

    public function writeLine(string $text, mixed ...$values): void
    {
        $this->doWrite($text, $values, 1);
    }

    public function newLine(int $count = 1): void
    {
        $this->doWrite(lineBreak: $count);
    }

    public function indent(int $count = 1): void
    {
        $this->indentCount += $count;
    }

    public function outdent(int $count = 1): void
    {
        $this->indentCount = \max(0, $this->indentCount - $count);
    }

    abstract protected function doWrite(string $text = '', array $values = [], int $lineBreak = 0): void;
}
