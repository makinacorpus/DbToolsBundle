<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Extends this class whenever you need either one of the connexion or the
 * the query builder from doctrine/dbal.
 *
 * Per default, it will yield the PostgreSQL database platform, it is meant
 * for writing unit test or anonymisers. If you need another dialect for a
 * certain test class, override setupUpPlatform() method to change it.
 *
 * If you need to test all dialects, either:
 *   - write unit test of every helper method you added,
 *   - write functional test that use the database for the test case.
 */
abstract class AbstractDatabaseUnitTestCase extends TestCase
{
    protected Connection&MockObject $connnection;
    protected AbstractPlatform $platform;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        // Code shamlessly copied from doctrine/dbal, all credits to their author.
        $this->connnection = $this->createMock(Connection::class);

        $expressionBuilder = new ExpressionBuilder($this->connnection);

        $this
            ->connnection
            ->expects(self::any())
            ->method('getExpressionBuilder')
            ->willReturn($expressionBuilder)
        ;

        $this->platform = $this->setupUpPlatform();

        $this
            ->connnection
            ->expects(self::any())
            ->method('getDatabasePlatform')
            ->willReturn($this->platform)
        ;
    }

    /**
     * Create database platform accordingly to environment configuration.
     *
     * We need a default one so for now, it's arbitrarily PostgreSQL.
     */
    protected function setupUpPlatform(): AbstractPlatform
    {
        return new PostgreSQLPlatform();
    }

    /**
     * Get testing connection object.
     */
    protected function getConnection(): Connection&MockObject
    {
        return $this->connnection;
    }

    /**
     * Create new query builder.
     */
    protected function getQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->connnection);
    }

    /**
     * Asserts that two variables yield the same SQL code.
     *
     * Provided SQL code will be normalized, lowercased, all whitespace
     * character removed, in order for the indentation to get out of the
     * way.
     */
    protected function assertSameSql(
        string|QueryBuilder|\Stringable $expected,
        string|QueryBuilder|\Stringable $actual,
        string $message = ''
    ): void {
        if ($expected instanceof QueryBuilder) {
            $expected = $expected->getSQL();
        }
        if ($actual instanceof QueryBuilder) {
            $actual = $actual->getSQL();
        }

        $expected = (string) $expected;
        $actual = (string) $actual;

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
