<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Test;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use MakinaCorpus\QueryBuilder\DefaultQueryBuilder;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\QueryBuilder;
use MakinaCorpus\QueryBuilder\SqlString;
use MakinaCorpus\QueryBuilder\Platform\Escaper\StandardEscaper;
use MakinaCorpus\QueryBuilder\Writer\Writer;
use PHPUnit\Framework\TestCase;

/**
 * Extends this class whenever you need either one of the connection or the
 * the query builder from doctrine/dbal.
 *
 * Per default, it will yield the PostgreSQL database platform, it is meant
 * for writing unit test or anonymisers. If you need another dialect for a
 * certain test class, override createPlatform() method to change it.
 *
 * If you need to test all dialects, either:
 *   - write unit test of every helper method you added,
 *   - write functional test that use the database for the test case.
 */
abstract class UnitTestCase extends TestCase
{
    private bool $inialized = false;
    private ?Connection $connection = null;

    /** @after */
    protected function disconnect(): void
    {
        if ($this->connection) {
            while ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            unset($this->connection);
        }
    }

    /**
     * Create database connection.
     *
     * Default will use a mock object.
     */
    protected function createConnection(): Connection
    {
        $connection = $this->createMock(Connection::class);

        $expressionBuilder = new ExpressionBuilder($connection);

        $connection
            ->expects(self::any())
            ->method('getExpressionBuilder')
            ->willReturn($expressionBuilder)
        ;

        $platform = $this->createPlatform();

        $connection
            ->expects(self::any())
            ->method('getDatabasePlatform')
            ->willReturn($platform)
        ;

        return $connection;
    }

    /**
     * Create database platform accordingly to environment configuration.
     *
     * We need a default one so for now, it's arbitrarily PostgreSQL.
     *
     * This method will be ignored in functional tests.
     */
    protected function createPlatform(): AbstractPlatform
    {
        return new PostgreSQLPlatform();
    }

    /**
     * Initialize database.
     */
    protected function initializeDatabase(): void {}

    /**
     * Get testing connection object.
     */
    protected function getConnection(): Connection
    {
        if (!$this->inialized) {
            $this->initializeDatabase();

            $this->inialized = true;
        }

        return $this->connection ??= $this->createConnection();
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
