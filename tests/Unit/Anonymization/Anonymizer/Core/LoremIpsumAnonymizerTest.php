<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\LoremIpsumAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Test\UnitTestCase;

class LoremIpsumAnonymizerTest extends UnitTestCase
{
    public function testValidateOptionsOkWithNoOption(): void
    {
        new LoremIpsumAnonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsOkWithWords(): void
    {
        new LoremIpsumAnonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([
                'words' => 15,
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsKoWithWordsLesserThan0(): void
    {
        self::expectExceptionMessageMatches("@greater@");

        new LoremIpsumAnonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([
                'words' => -15,
            ]),
        );
    }

    public function testValidateOptionsOkWithParagraphs(): void
    {
        new LoremIpsumAnonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([
                'paragraphs' => 15,
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsKoWithParagraphsLesserThan0(): void
    {
        self::expectExceptionMessageMatches("@greater@");

        new LoremIpsumAnonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([
                'paragraphs' => -15,
            ]),
        );
    }
}
