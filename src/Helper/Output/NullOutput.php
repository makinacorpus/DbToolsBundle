<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper\Output;

class NullOutput implements OutputInterface
{
    public function write(string $text, array $values = []): void
    {
    }

    public function writeLine(string $text, array $values = [], int $lineBreak = 1) : void
    {
    }

    public function newLine(int $count = 1) : void
    {
    }

    public function indent(int $count = 1) : void
    {
    }

    public function outdent(int $count = 1) : void
    {
    }
}
