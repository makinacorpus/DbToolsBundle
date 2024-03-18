<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\IntegerAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Test\UnitTestCase;

class IntegerAnonymizerTest extends UnitTestCase
{
    public function testValidateOptionsOkWithMinMax(): void
    {
        new IntegerAnonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([
                'min' => 12,
                'max' => 14,
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsKoWithMinGreaterThanMax(): void
    {
        self::expectExceptionMessageMatches("@greater@");

        new IntegerAnonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([
                'min' => 15,
                'max' => 14,
            ]),
        );
    }

    public function testValidateOptionsOkWithDelta(): void
    {
        new IntegerAnonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([
                'delta' => 12,
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsKoWithDeltaLesserThan0(): void
    {
        self::expectExceptionMessageMatches("@greater@");

        new IntegerAnonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([
                'delta' => -12,
            ]),
        );
    }

    public function testValidateOptionsOkWithPercent(): void
    {
        new IntegerAnonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([
                'percent' => 12,
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsKoWithPercentLesserThan0(): void
    {
        self::expectExceptionMessageMatches("@greater@");

        new IntegerAnonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([
                'percent' => -12,
            ]),
        );
    }

    public function testValidateOptionsKoWithTooManyOptions(): void
    {
        self::expectExceptionMessageMatches("@cannot be specified@");

        new IntegerAnonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([
                'min' => 12,
                'max' => 14,
                'delta' => 14,
            ]),
        );

        self::expectExceptionMessageMatches("@cannot be specified@");

        new IntegerAnonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([
                'min' => 12,
                'max' => 14,
                'percent' => 14,
            ]),
        );

        self::expectExceptionMessageMatches("@cannot be specified@");

        new IntegerAnonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([
                'delta' => 14,
                'percent' => 14,
            ]),
        );
    }

    public function testValidateOptionsKoWithoutOption(): void
    {
        self::expectExceptionMessageMatches("@must provide options@");

        new IntegerAnonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([]),
        );
    }
}
