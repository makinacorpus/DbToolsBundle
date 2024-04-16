<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\FloatAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Test\UnitTestCase;

class FloatAnonymizerTest extends UnitTestCase
{
    public function testValidateOptionsOkWithMinMax(): void
    {
        new FloatAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([
                'min' => 12.5,
                'max' => 14.5,
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsKoWithMinGreaterThanMax(): void
    {
        self::expectExceptionMessageMatches("@greater@");

        new FloatAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([
                'min' => 15.5,
                'max' => 14.5,
            ]),
        );
    }

    public function testValidateOptionsOkWithDelta(): void
    {
        new FloatAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([
                'delta' => 12.5,
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsKoWithDeltaLesserThan0(): void
    {
        self::expectExceptionMessageMatches("@greater@");

        new FloatAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([
                'delta' => -12.5,
            ]),
        );
    }

    public function testValidateOptionsOkWithPercent(): void
    {
        new FloatAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([
                'percent' => 12,
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsKoWithPercentLesserThan0(): void
    {
        self::expectExceptionMessageMatches("@greater@");

        new FloatAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([
                'percent' => -12,
            ]),
        );
    }

    public function testValidateOptionsKoWithTooManyOptions(): void
    {
        self::expectExceptionMessageMatches("@cannot be specified@");

        new FloatAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([
                'min' => 12.5,
                'max' => 14.5,
                'delta' => 14.5,
            ]),
        );

        self::expectExceptionMessageMatches("@cannot be specified@");

        new FloatAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([
                'min' => 12.5,
                'max' => 14.5,
                'percent' => 14.5,
            ]),
        );

        self::expectExceptionMessageMatches("@cannot be specified@");

        new FloatAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([
                'delta' => 14.5,
                'percent' => 14.5,
            ]),
        );
    }

    public function testValidateOptionsKoWithoutOption(): void
    {
        self::expectExceptionMessageMatches("@must provide options@");

        new FloatAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([]),
        );
    }
}
