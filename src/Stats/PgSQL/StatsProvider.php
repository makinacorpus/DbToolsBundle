<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Stats\PgSQL;

use MakinaCorpus\DbToolsBundle\Stats\AbstractStatsProvider;
use MakinaCorpus\DbToolsBundle\Stats\StatValue;
use MakinaCorpus\DbToolsBundle\Stats\StatValueList;

class StatsProvider extends AbstractStatsProvider
{
    /**
     * {@inheritdoc}
     */
    protected function doGetTableStats(): iterable
    {
        $result = $this->connection->executeQuery(
            <<<SQL
            SELECT * FROM (
                SELECT
                    -- Table information
                    concat(schemaname, '.', relname) as table_name,
                    -- Table and indices size
                    pg_total_relation_size(relid) as "size_total",
                    pg_table_size(relid) as "size_table",
                    pg_indexes_size(relid) as "size_index",
                    -- Read stats
                    seq_scan,
                    seq_tup_read,
                    idx_scan,
                    idx_tup_fetch,
                    -- Mutation stats
                    n_tup_ins,
                    n_tup_upd,
                    n_tup_hot_upd,
                    n_tup_del,
                    -- Rows state information
                    n_live_tup,
                    n_dead_tup,
                    n_mod_since_analyze,
                    -- VACUUM information
                    last_vacuum,
                    last_autovacuum,
                    last_analyze,
                    last_autoanalyze,
                    vacuum_count,
                    autovacuum_count,
                    analyze_count,
                    autoanalyze_count
                FROM pg_stat_user_tables
                UNION
                SELECT
                    -- Table information
                    concat(stats.relname,  ' [', class2.relname, ']') as table_name,
                    -- Table and indices size
                    pg_total_relation_size(relid) as "size_total",
                    pg_table_size(relid) as "size_table",
                    pg_indexes_size(relid) as "size_index",
                    -- Read stats
                    seq_scan,
                    seq_tup_read,
                    idx_scan,
                    idx_tup_fetch,
                    -- Mutation stats
                    n_tup_ins,
                    n_tup_upd,
                    n_tup_hot_upd,
                    n_tup_del,
                    -- Rows state information
                    n_live_tup,
                    n_dead_tup,
                    n_mod_since_analyze,
                    -- VACUUM information
                    last_vacuum,
                    last_autovacuum,
                    last_analyze,
                    last_autoanalyze,
                    vacuum_count,
                    autovacuum_count,
                    analyze_count,
                    autoanalyze_count
                FROM pg_stat_sys_tables stats
                JOIN pg_class class1 ON class1.relname = stats.relname
                JOIN pg_class class2 ON class2.reltoastrelid = class1.oid
            ) AS a
            ORDER BY size_total DESC;
            SQL
        );

        while ($row = $result->fetchAssociative()) {
            yield new StatValueList(
                name: $row['table_name'],
                help: <<<TXT
                Data is provided by the PostgreSQL cumulative statistics system.
                All counters, except for the table sizes, are relative to the latest server restart.
                TXT,
                stats: [
                    // Table and indices size.
                    new StatValue(
                        'size_total',
                        (int) $row['size_total'],
                        StatValue::UNIT_BYTE,
                        [StatValue::TAG_INFO],
                        "Total table size on disk."
                    ),
                    new StatValue(
                        'size_table',
                        (int) $row['size_table'],
                        StatValue::UNIT_BYTE,
                        [StatValue::TAG_INFO],
                        "Table data size on disk.",
                    ),
                    new StatValue(
                        'size_index',
                        (int) $row['size_index'],
                        StatValue::UNIT_BYTE,
                        [StatValue::TAG_INFO],
                        "Table index size on disk (contains all indices)."
                    ),
                    // Read stats.
                    new StatValue(
                        'seq_scan',
                        (int) $row['seq_scan'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_READ],
                        "Number of seq scan (aka full scan).",
                    ),
                    new StatValue(
                        'seq_tup_read',
                        (int) $row['seq_tup_read'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_READ],
                        "Number of rows fetched by seq scans.",
                    ),
                    new StatValue(
                        'idx_scan',
                        (int) $row['idx_scan'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_READ],
                        "Number of index scans."
                    ),
                    new StatValue(
                        'idx_tup_fetch',
                        (int) $row['idx_tup_fetch'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_READ],
                        "Number of rows fetched by index scan.",
                    ),
                    // Mutation stats.
                    new StatValue(
                        'n_tup_ins',
                        (int) $row['n_tup_ins'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_WRITE],
                        "Total number of row inserted."
                    ),
                    new StatValue(
                        'n_tup_upd',
                        (int) $row['n_tup_upd'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_WRITE],
                        "Total number of row updated."
                    ),
                    new StatValue(
                        'n_tup_hot_upd',
                        (int) $row['n_tup_hot_upd'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_WRITE],
                        "Total number of row HOT updated (faster than classic updates)."
                    ),
                    new StatValue(
                        'n_tup_del',
                        (int) $row['n_tup_del'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_WRITE],
                        "Total number of row deleted."
                    ),
                    // Rows state information.
                    new StatValue(
                        'n_live_tup',
                        (int) $row['n_live_tup'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_INFO],
                        "Estimated number of alive rows (rows that exists)."
                    ),
                    new StatValue(
                        'n_dead_tup',
                        (int) $row['n_dead_tup'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_INFO],
                        "Estimated number of dead row (rows that need to be cleaned up using VACUUM)."
                    ),
                    new StatValue(
                        'n_mod_since_analyze',
                        (int) $row['n_mod_since_analyze'],
                        StatValue::UNIT_DATETIME,
                        [StatValue::TAG_INFO],
                        "Estimated number of rows modified since this table was last analyzed.",
                    ),
                    // VACUUM information.
                    new StatValue(
                        'last_vacuum',
                        $row['last_vacuum'] ? new \DateTimeImmutable($row['last_vacuum']) : null,
                        StatValue::UNIT_DATETIME,
                        [StatValue::TAG_MAINTENANCE],
                        "Last time at which this table was manually vacuumed (not counting VACUUM FULL).",
                    ),
                    new StatValue(
                        'last_autovacuum',
                        $row['last_autovacuum'] ? new \DateTimeImmutable($row['last_autovacuum']) : null,
                        StatValue::UNIT_DATETIME,
                        [StatValue::TAG_MAINTENANCE],
                        "Last time at which this table was vacuumed by the autovacuum daemon.",
                    ),
                    new StatValue(
                        'last_analyze',
                        $row['last_analyze'] ? new \DateTimeImmutable($row['last_analyze']) : null,
                        StatValue::UNIT_DATETIME,
                        [StatValue::TAG_MAINTENANCE],
                        "Last time at which this table was manually analyzed.",
                    ),
                    new StatValue(
                        'last_autoanalyze',
                        $row['last_autoanalyze'] ? new \DateTimeImmutable($row['last_autoanalyze']) : null,
                        StatValue::UNIT_DATETIME,
                        [StatValue::TAG_MAINTENANCE],
                        "Last time at which this table was analyzed by the autovacuum daemon.",
                    ),
                    new StatValue(
                        'vacuum_count',
                        (int) $row['vacuum_count'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_MAINTENANCE],
                        "Number of times this table has been manually vacuumed (not counting VACUUM FULL).",
                    ),
                    new StatValue(
                        'autovacuum_count',
                        (int) $row['autovacuum_count'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_MAINTENANCE],
                        "Number of times this table has been vacuumed by the autovacuum daemon.",
                    ),
                    new StatValue(
                        'analyze_count',
                        (int) $row['analyze_count'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_MAINTENANCE],
                        "Number of times this table has been manually analyzed.",
                    ),
                    new StatValue(
                        'autoanalyze_count',
                        (int) $row['autoanalyze_count'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_MAINTENANCE],
                        "Number of times this table has been analyzed by the autovacuum daemon.",
                    ),
                ],
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetIndexStats(): iterable
    {
        $result = $this->connection->executeQuery(
            <<<SQL
            SELECT
                concat(stats.schemaname, '.', stats.relname) as table_name,
                stats.indexrelname AS index,
                idx.indisprimary AS primary,
                idx.indisunique AS unique,
                stats.idx_scan,
                stats.idx_tup_read,
                stats.idx_tup_fetch,
                pg_table_size(stats.schemaname || '.' || stats.indexrelname::text) as index_disk_size,
                pg_catalog.pg_get_indexdef(idx.indexrelid, 0, true) AS def
            FROM pg_catalog.pg_stat_user_indexes stats
            JOIN pg_catalog.pg_index idx ON (stats.indexrelid = idx.indexrelid)
            LEFT JOIN  pg_catalog.pg_constraint con
                ON (
                    conrelid = idx.indrelid AND
                    conindid = idx.indexrelid AND
                    contype IN ('p','u','x')
                )
            ORDER BY
                pg_table_size(stats.schemaname || '.' || stats.indexrelname::text) DESC,
                stats.schemaname,
                stats.relname,
                stats.idx_scan DESC
            LIMIT 50
            SQL
        );

        while ($row = $result->fetchAssociative()) {
            yield new StatValueList(
                name: $row['index'],
                stats: [
                    // Table and indices size.
                    new StatValue(
                        'table_name',
                        $row['table_name'],
                        StatValue::UNIT_NAME,
                        [StatValue::TAG_INFO],
                        "Table name."
                    ),
                    new StatValue(
                        'index_disk_size',
                        (int) $row['index_disk_size'],
                        StatValue::UNIT_BYTE,
                        [StatValue::TAG_INFO],
                        "Index size on disk.",
                    ),
                    new StatValue(
                        'primary',
                        (bool) $row['primary'],
                        StatValue::UNIT_BOOL,
                        [StatValue::TAG_INFO],
                        "Is primary.",
                    ),
                    new StatValue(
                        'unique',
                        (bool) $row['unique'],
                        StatValue::UNIT_BOOL,
                        [StatValue::TAG_INFO],
                        "Is unique."
                    ),
                    // Read stats.
                    new StatValue(
                        'idx_scan',
                        (int) $row['idx_scan'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_READ],
                        "Number of index scans.",
                    ),
                    new StatValue(
                        'idx_tup_read',
                        (int) $row['idx_tup_read'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_READ],
                        "Number of rows fetched by index scan.",
                    ),
                    new StatValue(
                        'idx_tup_fetch',
                        (int) $row['idx_tup_fetch'],
                        StatValue::UNIT_UNIT,
                        [StatValue::TAG_READ],
                        "Number of rows fetched by index scan."
                    ),
                    // Mutation stats.
                    new StatValue(
                        'def',
                        $row['def'],
                        StatValue::UNIT_CODE,
                        [StatValue::TAG_CODE],
                        "Index add SQL statement."
                    ),
                ],
            );
        }
    }
}
