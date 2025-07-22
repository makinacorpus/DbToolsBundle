<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Pattern;

class RawPart implements Part
{
    public function __construct(
        public readonly string $string,
    ) {}

    /**
     * @internal
     *   Mostly used in unit tests.
     */
    #[\Override]
    public function __toString(): string
    {
        return '"' . $this->string . '"';
    }
}
