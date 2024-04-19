<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Stats;

use MakinaCorpus\QueryBuilder\DatabaseSession;

/**
 * Every RDMBS will have different statistics to restitute, this cannot be
 * generalized, this method will return TableStatList instances, which
 * themselves will hold a list of arbitrary values.
 *
 * Which value you will find will depend upon RDBMS implementation.
 */
abstract class AbstractStatsProvider
{
    public function __construct(
        protected DatabaseSession $databaseSession
    ) {}

    /**
     * Get table statistics.
     *
     * @param null|string[] $tags
     *   Filter using tags. See StatValue::TAG_* constants.
     *
     * @return StatValueList[]
     */
    public function getTableStats(?array $tags = null): iterable
    {
        return $this->filter($this->doGetTableStats(), $tags);
    }

    /**
     * Implementation of getTableStats() without filter.
     */
    protected function doGetTableStats(): iterable
    {
        return [];
    }

    /**
     * Get indices statistics.
     *
     * @param null|string[] $tags
     *   Filter using tags. See StatValue::TAG_* constants.
     *
     * @return StatValueList[]
     */
    public function getIndexStats(?array $tags): iterable
    {
        return $this->filter($this->doGetIndexStats(), $tags);
    }

    /**
     * Implementation of getTableStats() without filter.
     */
    protected function doGetIndexStats(): iterable
    {
        return [];
    }

    /**
     * Get global information.
     *
     * @param null|string[] $tags
     *   Filter using tags. See StatValue::TAG_* constants.
     *
     * @return StatValueList[]
     */
    public function getGlobalStats(?array $tags): iterable
    {
        return $this->filter($this->doGetGlobalStats(), $tags);
    }

    /**
     * Implementation of getTableStats() without filter.
     */
    protected function doGetGlobalStats(): iterable
    {
        return [];
    }

    /**
     * For global statistics, create a single valued line.
     */
    protected function singleValueLine(
        string $name,
        null|bool|float|int|string|\DateTimeImmutable $value,
        string $unit = StatValue::UNIT_UNIT,
        array $tags = [],
        ?string $description = null,
    ): StatValueList {
        return new StatValueList(
            name: $name,
            stats: [
                new StatValue(
                    'value',
                    $value,
                    $unit,
                    $tags,
                )
            ],
            help: $description,
        );
    }

    /**
     * Filter given list of collections using tags.
     */
    protected function filter(iterable $collections, ?array $tags = null): iterable
    {
        foreach ($collections as $collection) {
            \assert($collection instanceof StatValueList);

            if ($tags) {
                $values = [];
                foreach ($collection as $value) {
                    \assert($value instanceof StatValue);

                    if (\array_intersect($tags, $value->tags)) {
                        $values[] = $value;
                    }
                }

                yield new StatValueList($collection->name, $values);
            } else {
                yield $collection;
            }
        }
    }
}
