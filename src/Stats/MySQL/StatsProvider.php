<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Stats\MySQL;

use MakinaCorpus\DbToolsBundle\Stats\AbstractStatsProvider;
use MakinaCorpus\DbToolsBundle\Stats\StatValue;
use MakinaCorpus\DbToolsBundle\Stats\StatValueList;

class StatsProvider extends AbstractStatsProvider
{
    /**
     * {@inheritdoc}
     */
    protected function doGetGlobalStats(): iterable
    {
        $result = $this->connection->executeQuery(
            <<<SQL
            SELECT
                sum(DATA_LENGTH) AS total_table_size,
                sum(INDEX_LENGTH) AS total_index_size
            FROM information_schema.TABLES
            WHERE
                TABLE_TYPE = 'BASE TABLE'
                AND TABLE_SCHEMA NOT IN (
                    'sys', 'information_schema', 'mysql', 'performance_schema'
                )
            SQL
        );

        while ($row = $result->fetchAssociative()) {
            yield $this->singleValueLine(
                'total_table_size',
                (int) $row['total_table_size'],
                StatValue::UNIT_BYTE,
                [StatValue::TAG_INFO],
                "Total table size."
            );
            yield $this->singleValueLine(
                'total_index_size',
                (int) $row['total_index_size'],
                StatValue::UNIT_BYTE,
                [StatValue::TAG_INFO],
                "Total index size."
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetTableStats(): iterable
    {
        $result = $this->connection->executeQuery(
            <<<SQL
            SELECT
                concat(TABLE_SCHEMA, '.', TABLE_NAME) AS table_name,
                DATA_LENGTH AS table_size,
                INDEX_LENGTH AS index_size,
                TABLE_ROWS AS row_count,
                DATA_FREE AS data_free,
                ENGINE AS engine
            FROM information_schema.TABLES
            WHERE
                TABLE_TYPE = 'BASE TABLE'
                AND TABLE_SCHEMA NOT IN (
                    'sys', 'information_schema', 'mysql', 'performance_schema'
                )
            ORDER BY DATA_LENGTH DESC
            SQL
        );

        while ($row = $result->fetchAssociative()) {
            yield new StatValueList(
                name: $row['table_name'],
                stats: [
                    // Table and indices size.
                    new StatValue(
                        'table_size',
                        (int) $row['table_size'],
                        StatValue::UNIT_BYTE,
                        [StatValue::TAG_INFO],
                        "Table size."
                    ),
                    new StatValue(
                        'index_size',
                        (int) $row['index_size'],
                        StatValue::UNIT_BYTE,
                        [StatValue::TAG_INFO],
                        "Index size."
                    ),
                    new StatValue(
                        'data_free',
                        (int) $row['data_free'],
                        StatValue::UNIT_BYTE,
                        [StatValue::TAG_INFO],
                        "Free space to use."
                    ),
                    new StatValue(
                        'row_count',
                        (int) $row['row_count'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_INFO],
                        "Row count."
                    ),
                    new StatValue(
                        'engine',
                        (int) $row['engine'],
                        StatValue::UNIT_NAME,
                        [StatValue::TAG_INFO],
                        "Engine."
                    ),
                ],
            );
        }
    }
}
