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
     * directives from the values provided as second argument.
     */
    public function write(string $text, array $values = []): void;

    /**
     * Write the given text in the output channel by ending with one or more
     * line breaks depending on the last argument value.
     *
     * The text can include the kind of directives expected by the \sprintf()
     * function. We assume that implementations use this function to resolve
     * directives from the values provided as second argument.
     */
    public function writeLine(string $text, array $values = [], int $lineBreak = 1): void;

    public function newLine(int $count = 1): void;

    public function indent(int $count = 1): void;

    public function outdent(int $count = 1): void;
}
