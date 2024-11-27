<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Expression;

use MakinaCorpus\DbToolsBundle\Error\DatasourceException;

/**
 * Where the magic happens.
 */
class Parser
{
    private int $offset = -1;
    private int $length;

    public function __construct(
        /** @var string User text. */
        private string $raw,
        /** @var string Datasource in which this expression is found. */
        private string $datasource,
        /** @var int Expression number in datasource. */
        private int $number,
    ) {
        $this->length = \strlen($raw);
    }

    /**
     * Parse expression string.
     *
     * @return Token[]
     */
    public function parse(): array
    {
        $tokens = [];
        $text = '';
        $startOffset = 0;

        while (true) {
            try {
                $cur = $this->nextChar();
            } catch (\OutOfBoundsException) { // End of text.
                if ($text) {
                    $tokens[] = new Text($this->datasource, $this->number, $startOffset, $text);
                }
                return $tokens;
            }

            if ('{' === $cur) {
                $next = $this->nextChar();
                if ('{' === $next) {
                    if ($text) {
                        $tokens[] = new Text($this->datasource, $this->number, $startOffset, $text);
                        $text = '';
                    }
                    $startOffset = $this->offset - 1; // parseRef() shifts the offset.
                    $tokens[] = new Reference($this->datasource, $this->number, $startOffset, $this->parseRef());
                    $startOffset = $this->offset + 1;
                } else {
                    $text .= $cur . $next;
                }
            } else if ('[' === $cur) {
                if ($text) {
                    $tokens[] = new Text($this->datasource, $this->number, $startOffset, $text);
                    $text = '';
                }
                $startOffset = $this->offset; // parseRange() shifts the offset.
                $tokens[] = new Range($this->datasource, $this->number, $startOffset, ...$this->parseRange());
                $startOffset = $this->offset + 1;
            } else {
                $text .= $cur;
            }
        }
    }

    private function throwError(string|\Throwable $error, ?int $offset = null): never
    {
        $prefix = \sprintf('Datasource "%s" expression #%d at offset %d: ', $this->datasource, $this->number, $offset ?? $this->offset);
        if ($error instanceof \Throwable) {
            throw new DatasourceException($prefix . $error->getMessage(), 0, $error);
        }
        throw new DatasourceException($prefix . $error);
    }

    private function nextChar(): string
    {
        if ($this->offset >= ($this->length - 1)) {
            throw new \OutOfBoundsException(); // Flow control.
        }
        return $this->raw[++$this->offset];
    }

    private function parseRange(): array
    {
        $min = $this->parseInt();
        if (!\in_array($this->nextChar(), [',', ';'])) {
            $this->throwError("invalid integer range.");
        }
        $max = $this->parseInt();
        if (']' !== $this->nextChar()) {
            $this->throwError("invalid integer range.");
        }
        // @phpstan-ignore-next-line
        return $min < $max ? [$min, $max] : [$max, $min];
    }

    private function parseInt(): int
    {
        $ret = '';
        $negative = false;
        while (true) {
            $next = $this->nextChar();
            if (\ctype_digit($next)) {
                $ret .= $next;
            } else if ($ret) {
                $this->offset--;
                return $negative ? (0 - \intval($ret)) : \intval($ret);
            } else if ('+' === $next) { // Positive integer.
            } else if ('-' === $next) { // Negative integer.
                $negative = true;
            } else {
                $this->throwError("value is not a valid integer.");
            }
        }
    }

    private function parseRef(): string
    {
        $ret = '';
        while (true) {
            $cur = $this->nextChar();
            if ($cur === '}') {
                $next = $this->nextChar();
                if ($next === '}') {
                    // End of reference.
                    return $ret;
                } else {
                    $ret .= $cur . $next;
                }
            } else {
                $ret .= $cur;
            }
        }
    }
}
