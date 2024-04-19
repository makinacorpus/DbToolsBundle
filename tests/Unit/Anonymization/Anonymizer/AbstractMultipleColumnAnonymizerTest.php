<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractMultipleColumnAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Test\UnitTestCase;

class AbstractMultipleColumnAnonymizerTest extends UnitTestCase
{
    public function testValidateOptionsOkWithAllColumnOption(): void
    {
        new TestMultipleColumnAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([
                'column_1' => 'actual_column_1',
                'column_2' => 'actual_column_2',
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsOkWithSomeColumnOption(): void
    {
        new TestMultipleColumnAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([
                'column_2' => 'actual_column_2',
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsKoWithNoOption(): void
    {
        self::expectExceptionMessageMatches("@must provide at least@");

        new TestMultipleColumnAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([]),
        );
    }

    public function testValidateOptionsKoWithColumnMapedTwice(): void
    {
        self::expectExceptionMessageMatches("@same column@");

        new TestMultipleColumnAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([
                'column_1' => 'actual_column_1',
                'column_2' => 'actual_column_1',
            ]),
        );
    }
}

class TestMultipleColumnAnonymizer extends AbstractMultipleColumnAnonymizer
{
    #[\Override]
    protected function getColumnNames(): array
    {
        return ['column_1', 'column_2'];
    }

    #[\Override]
    protected function getSamples(): array
    {
        return [
            'column_1' => ['value_test1', 'value_test2'],
            'column_2' => ['value_test1', 'value_test2'],
        ];
    }
};
