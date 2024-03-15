<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper\Output;

class NullOutput implements OutputInterface
{
    #[\Override]
    public function write(string $text, mixed ...$values): void
    {
    }

    #[\Override]
    public function writeLine(string $text, mixed ...$values): void
    {
    }

    #[\Override]
    public function newLine(int $count = 1): void
    {
    }

    #[\Override]
    public function indent(int $count = 1): void
    {
    }

    #[\Override]
    public function outdent(int $count = 1): void
    {
    }
}
