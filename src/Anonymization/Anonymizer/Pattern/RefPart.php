<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Pattern;

class RefPart implements Part
{
    public readonly string $id;

    public function __construct(
        public readonly string $anonymizerId,
        public readonly ?string $column = null,
        public readonly ?string $packId = null,
        public readonly int $delta = 0
    ) {
        if ('core' === $this->packId || null === $this->packId) {
            $this->id = $anonymizerId;
        } else {
            $this->id = \sprintf("%s.%s", $this->packId, $this->anonymizerId);
        }
    }

    /**
     * @internal
     *   Mostly used in unit tests.
     */
    #[\Override]
    public function __toString(): string
    {
        return \sprintf("ref:{%s.%s:%s}[%d]", $this->packId, $this->anonymizerId, $this->column ?? 0, $this->delta);
    }
}
