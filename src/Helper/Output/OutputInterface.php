<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper\Output;

interface OutputInterface
{
    /**
     * Write the given text in the output channel.
     *
     * The text can include the kind of directives expected by the \sprintf()
     * function. We assume that implementations use this function to resolve
     * directives from the other argument values.
     */
    public function write(string $text, mixed ...$values): void;

    /**
     * Write the given text in the output channel by ending with one line break.
     *
     * The text can include the kind of directives expected by the \sprintf()
     * function. We assume that implementations use this function to resolve
     * directives from the other argument values.
     */
    public function writeLine(string $text, mixed ...$values): void;

    public function newLine(int $count = 1): void;

    public function indent(int $count = 1): void;

    public function outdent(int $count = 1): void;
}
