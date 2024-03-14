<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Stats;

use IteratorAggregate;
use Traversable;
/**
 * @type iterable<StatValue>
 */
class StatValueList implements IteratorAggregate
{
    public function __construct(
        public string $name,
        private array $stats,
        public ?string $help = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Traversable
    {
        yield from $this->stats;
    }
}
