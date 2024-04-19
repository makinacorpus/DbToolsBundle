<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\ConstantAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Test\UnitTestCase;

class ConstantAnonymizerTest extends UnitTestCase
{
    public function testValidateOptionsOkWithValueOption(): void
    {
        new ConstantAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([
                'value' => 'test',
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsKoWithValueOption(): void
    {
        self::expectExceptionMessageMatches("@required@");

        new ConstantAnonymizer(
            'some_table',
            'some_column',
            $this->getDatabaseSession(),
            new Options([]),
        );
    }
}
