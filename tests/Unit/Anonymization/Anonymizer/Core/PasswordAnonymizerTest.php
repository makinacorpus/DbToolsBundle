<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Context;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\PasswordAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Test\UnitTestCase;

class PasswordAnonymizerTest extends UnitTestCase
{
    public function testValidateOptionsOkWithNoOption(): void
    {
        new PasswordAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Context(),
            new Options(),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsOkWithPassword(): void
    {
        new PasswordAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Context(),
            new Options([
                'password' => 'test',
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsOkWithAlgorithm(): void
    {
        new PasswordAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Context(),
            new Options([
                'algorithm' => 'bcrypt',
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsKoWithUnknownAlgorithm(): void
    {
        self::expectExceptionMessageMatches("@invalid@");

        new PasswordAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Context(),
            new Options([
                'algorithm' => 'toto',
            ]),
        );
    }
}
