<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Test;

use MakinaCorpus\QueryBuilder\Bridge\Mock\MockQueryBuilder;
use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\DefaultQueryBuilder;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\QueryBuilder;
use MakinaCorpus\QueryBuilder\SqlString;
use MakinaCorpus\QueryBuilder\Writer\Writer;
use PHPUnit\Framework\TestCase;

/**
 * Extends this class whenever you need either the connection or the query
 * builder from doctrine/dbal.
 *
 * Per default, it will yield the PostgreSQL database platform, and it is
 * hardcoded.
 *
 * If you need to test all dialects, either:
 *   - write unit test of every helper method you added,
 *   - write functional test that use the database for the test case.
 */
abstract class UnitTestCase extends TestCase
{
    /**
     * Create new query builder.
     */
    protected function getDatabaseSession(): DatabaseSession
    {
        return new MockQueryBuilder();
    }

    /**
     * Create new query builder.
     */
    protected function getQueryBuilder(): QueryBuilder
    {
        return new DefaultQueryBuilder();
    }

    /**
     * Get prepared SQL.
     */
    protected function prepareSql(string|Expression|\Stringable $input): SqlString
    {
        if ($input instanceof SqlString) {
            return $input;
        }
        return (new Writer(new StandardEscaper('#', 1)))->prepare($input);
    }

    /**
     * Asserts that two variables yield the same SQL code.
     *
     * Provided SQL code will be normalized, lowercased, all whitespace
     * character removed, in order for the indentation to get out of the
     * way.
     */
    protected function assertSameSql(
        string|Expression|\Stringable $expected,
        string|Expression|\Stringable $actual,
        string $message = ''
    ): void {
        $expected = $this->prepareSql($expected);
        $actual = $this->prepareSql($actual);

        if ($message) {
            self::assertSame(
                self::normalizeSql((string) $expected),
                self::normalizeSql((string) $actual),
                $message
            );
        }

        self::assertSame(
            self::normalizeSql((string) $expected),
            self::normalizeSql((string) $actual)
        );
    }

    /**
     * Normalize SQL for comparison.
     */
    protected static function normalizeSql($string)
    {
        $string = \preg_replace('@\s*(\(|\))\s*@ms', '$1', $string);
        $string = \preg_replace('@\s*,\s*@ms', ',', $string);
        $string = \preg_replace('@\s+@ms', ' ', $string);
        $string = \strtolower($string);
        $string = \trim($string);

        return $string;
    }
}
