<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Pattern;

use MakinaCorpus\DbToolsBundle\Error\ConfigurationException;

class StringPattern
{
    private const GRAMMAR = '@(
            \[[^\]]*\]|     # Range
            \{[^\{\}]*\}    # Reference
        )@x';

    // Matches [n,m], [n;m], [n:m], [n-m]
    private const GRAMMAR_RANGE = '@\[((-|)\d+)[\:\;\-\,\-]((-|)\d+)\]@';
    // Matches {foo}, {foo.bar}, {bar:baz}, {foo.bar:baz}
    private const GRAMMAR_REF = '@{([^\.\:]+)(\.[^\:]+|)(\:.+|)}@';

    /** array<Part> */
    public readonly array $parts;

    public function __construct(
        public readonly string $raw,
        public readonly ?string $packId = null,
    ) {
        $this->parts = $this->parseString($this->raw);
    }

    private function parseString(string $string): array
    {
        $ret = [];
        $length = \strlen($string);
        $maxOffset = 0;

        $matches = [];
        if (\preg_match_all(self::GRAMMAR, $string, $matches, \PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {

                $text = $match[0];
                $first = $text[0];
                $startOffset = (int) $match[1];
                $stopOffset = $startOffset + \strlen($text);

                // Considering they are in order, if there is a gap between
                // max offset until now and this match own starting offset,
                // then the gap between the two is a raw string.
                if ($maxOffset + 1 < $startOffset) {
                    if (0 === $maxOffset) {
                        // Header raw string.
                        $ret[] = new RawPart(\substr($string, 0, $startOffset));
                    } else {
                        $ret[] = new RawPart(\substr($string, $maxOffset, $startOffset - $maxOffset));
                    }
                }

                // Will help fetching trailing data that is not matched by regex.
                if ($stopOffset > $maxOffset) {
                    $maxOffset = $stopOffset;
                }

                if ('[' === $first) {
                    $sub = [];
                    if (\preg_match(self::GRAMMAR_RANGE, $text, $sub)) {
                        $start = \intval($sub[1]);
                        $stop = \intval($sub[3]);
                        if ($start <= $stop) {
                            $ret[] = new IntRangePart($start, $stop);
                        } else {
                            $ret[] = new IntRangePart($stop, $start);
                        }
                    } else {
                        throw new ConfigurationException(\sprintf("Invalid range at offset %d in pattern: %s", $startOffset, $string));
                    }
                } elseif ('{' === $first) {
                    if (\preg_match(self::GRAMMAR_REF, $text, $sub)) {
                        if ($sub[2]) {
                            $packId = $sub[1];
                            $anonymizerId = \substr($sub[2], 1);
                        } else {
                            $packId = $this->packId;
                            $anonymizerId = $sub[1];
                        }
                        if ($sub[3]) {
                            $column = \substr($sub[3], 1);
                        } else {
                            $column = null;
                        }
                        $ret[] = new RefPart($anonymizerId, $column, $packId);
                    } else {
                        throw new ConfigurationException(\sprintf("Invalid anonymizer reference at offset %d in pattern: %s", $startOffset, $string));
                    }
                } else {
                    // Should not happen.
                    throw new \Exception("Invalid regex.");
                }
            }

            // Catch trailing data.
            if ($maxOffset < $length - 1) {
                $ret[] = new RawPart(\substr($string, $maxOffset));
            }
        } else {
            $ret[] = new RawPart($string);
        }

        return $ret;
    }
}
