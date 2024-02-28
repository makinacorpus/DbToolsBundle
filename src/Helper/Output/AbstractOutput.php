<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper\Output;

abstract class AbstractOutput implements OutputInterface
{
    protected int $indentCount = 0;

    public function __construct(
        protected int $indentSize = 4
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

    abstract protected function doWrite(?string $text, int $lineBreak = 0): void;
}
