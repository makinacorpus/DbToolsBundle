<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Context;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\IbanBicAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Test\UnitTestCase;

class IbanBicAnonymizerTest extends UnitTestCase
{
    public function testValidateOptionsOkWithNoOptionButColumns(): void
    {
        new IbanBicAnonymizer(
            'some_table',
            'iban',
            $this->getDatabaseSession(),
            new Context(),
            new Options([
                'iban' => 'foo_iban',
                'bic' => 'foo_bic',
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsOkWithAllOptions(): void
    {
        new IbanBicAnonymizer(
            'some_table',
            'iban',
            $this->getDatabaseSession(),
            new Context(),
            new Options([
                'iban' => 'foo_iban',
                'bic' => 'foo_bic',
                'sample_size' => 1000,
                'country' => 'fr',
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsKoWithNegativeSampleSize(): void
    {
        self::expectExceptionMessageMatches("@positive integer@");

        new IbanBicAnonymizer(
            'some_table',
            'iban',
            $this->getDatabaseSession(),
            new Context(),
            new Options([
                'iban' => 'foo_iban',
                'bic' => 'foo_bic',
                'sample_size' => -2,
            ]),
        );
    }

    public function testValidateOptionsKoWithZeroSampleSize(): void
    {
        self::expectExceptionMessageMatches("@positive integer@");

        new IbanBicAnonymizer(
            'some_table',
            'iban',
            $this->getDatabaseSession(),
            new Context(),
            new Options([
                'iban' => 'foo_iban',
                'bic' => 'foo_bic',
                'sample_size' => 0,
            ]),
        );
    }

    public function testValidateOptionsKoWithCountryTooLong(): void
    {
        self::expectExceptionMessageMatches("@2-letters country code@");

        new IbanBicAnonymizer(
            'some_table',
            'iban',
            $this->getDatabaseSession(),
            new Context(),
            new Options([
                'iban' => 'foo_iban',
                'bic' => 'foo_bic',
                'country' => 'fooooo',
            ]),
        );
    }

    public function testValidateOptionsKoWithCountryNotLetters(): void
    {
        self::expectExceptionMessageMatches("@2-letters country code@");

        new IbanBicAnonymizer(
            'some_table',
            'iban',
            $this->getDatabaseSession(),
            new Context(),
            new Options([
                'iban' => 'foo_iban',
                'bic' => 'foo_bic',
                'country' => '32',
            ]),
        );
    }

    public function testValidateOptionsKoWithCountryNotString(): void
    {
        self::expectExceptionMessageMatches("@2-letters country code@");

        new IbanBicAnonymizer(
            'some_table',
            'iban',
            $this->getDatabaseSession(),
            new Context(),
            new Options([
                'iban' => 'foo_iban',
                'bic' => 'foo_bic',
                'country' => 12,
            ]),
        );
    }
}
