<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Pattern;

class IntRangePart implements Part
{
    public function __construct(
        public readonly int $start,
        public readonly int $stop,
    ) {}

    /**
     * @internal
     *   Mostly used in unit tests.
     */
    #[\Override]
    public function __toString(): string
    {
        return \sprintf("intrange:[%d,%d]", $this->start, $this->stop);
    }
}
