<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Datasource;

use MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Context;
use MakinaCorpus\DbToolsBundle\Anonymization\Datasource\EnumDatasource;
use MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Expression;
use MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Expression\Range;
use MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Expression\Reference;
use MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Expression\Text;
use PHPUnit\Framework\TestCase;

class ExpressionTest extends TestCase
{
    public function testExecute(): void
    {
        $context = new Context([
            new EnumDatasource(
                'foo',
                ['some value'],
            ),
            new EnumDatasource(
                'bar',
                ['some other value'],
                [
                    '[7,7] then {{foo}} or {{bar}}',
                ],
            ),
        ]);

        self::assertSame(
            '7 then some value or some other value',
            $context->getDatasource('bar')->random($context),
        );
    }

    protected function createArbitraryExpression(string $raw): Expression
    {
        return new Expression($raw, 'arbitrary_datasource', 666);
    }

    public function testParseDatasourceFetch(): void
    {
        $expression = $this->createArbitraryExpression('Fetched: {{foo}}');

        self::assertEquals(
            [
                new Text('arbitrary_datasource', 666, 0, 'Fetched: '),
                new Reference('arbitrary_datasource', 666, 9, 'foo'),
            ],
            $expression->toArray(),
        );
    }

    public function testParseRange(): void
    {
        $expression = $this->createArbitraryExpression('[-5;+734]');

        self::assertEquals(
            [
                new Range('arbitrary_datasource', 666, 0, -5, 734),
            ],
            $expression->toArray(),
        );
    }

    public function testParseInversedRange(): void
    {
        $expression = $this->createArbitraryExpression('[5;-14]');

        self::assertEquals(
            [
                new Range('arbitrary_datasource', 666, 0, -14, 5),
            ],
            $expression->toArray(),
        );
    }

    public function testParseManyDatasourceFetch(): void
    {
        $expression = $this->createArbitraryExpression('{{foo}} is {{bar}}');

        self::assertEquals(
            [
                new Reference('arbitrary_datasource', 666, 0, 'foo'),
                new Text('arbitrary_datasource', 666, 7, ' is '),
                new Reference('arbitrary_datasource', 666, 11, 'bar'),
            ],
            $expression->toArray(),
        );
    }

    public function testParseAllInOneFetch(): void
    {
        $expression = $this->createArbitraryExpression('[12,134]{{foo}} -> {{bar}}@[1,2] {{bla}}');

        self::assertEquals(
            [
                new Range('arbitrary_datasource', 666, 0, 12, 134),
                new Reference('arbitrary_datasource', 666, 8, 'foo'),
                new Text('arbitrary_datasource', 666, 15, ' -> '),
                new Reference('arbitrary_datasource', 666, 19, 'bar'),
                new Text('arbitrary_datasource', 666, 26, '@'),
                new Range('arbitrary_datasource', 666, 27, 1, 2),
                new Text('arbitrary_datasource', 666, 32, ' '),
                new Reference('arbitrary_datasource', 666, 33, 'bla'),
            ],
            $expression->toArray(),
        );
    }
}
