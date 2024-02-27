<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper\Output;

interface OutputInterface
{
    public function write(string $text): void;

    public function writeLine(string $text, int $lineBreak = 1): void;

    public function newLine(int $count = 1): void;

    public function indent(int $count = 1): void;

    public function outdent(int $count = 1): void;
}
