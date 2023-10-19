<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Stats;

/**
 * Every RDMBS will have different statistics to restitute, this cannot be
 * generalized, this method will return TableStatList instances, which
 * themselves will hold a list of arbitrary values.
 *
 * Which value you will find will depend upon RDBMS implementation.
 */
interface StatsProvider
{
    /**
     * Get table statistics.
     *
     * @param null|string[] $tags
     *   Filter using tags. See StatValue::TAG_* constants.
     *
     * @return StatValueList[]
     */
    public function getTableStats(?array $tags): iterable;

    /**
     * Get indices statistics.
     *
     * @param null|string[] $tags
     *   Filter using tags. See StatValue::TAG_* constants.
     *
     * @return StatValueList[]
     */
    public function getIndexStats(?array $tags): iterable;

    /**
     * Get global information.
     *
     * @param null|string[] $tags
     *   Filter using tags. See StatValue::TAG_* constants.
     *
     * @return StatValueList[]
     */
    public function getGlobalStats(?array $tags): iterable;
}
