<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\FrFR;

use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Tests\FunctionalTestCase;

class PhoneNumberAnonymizerTest extends FunctionalTestCase
{
    /** @before */
    protected function createTestData(): void
    {
        $this->createOrReplaceTable(
            'table_with_phone',
            [
                'id' => 'integer',
                'phone_column' => 'string',
            ],
            [
                [
                    'id' => '1',
                    'phone_column' => "'0234567834'",
                ],
                [
                    'id' => '2',
                    'phone_column' => "'0334567234'",
                ],
                [
                    'id' => '3',
                    'phone_column' => "'0534567234'",
                ],
                [
                    'id' => '4',
                ],
            ],
        );
    }

    public function testAnonymize(): void
    {
        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_with_phone',
            'phone_column',
            'fr_fr.phone',
            new Options()
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(),
            $config
        );

        $this->assertSame(
            "0234567834",
            $this->getConnection()->executeQuery('select phone_column from table_with_phone where id = 1')->fetchOne(),
        );

        foreach ($anonymizator->anonymize() as $message) {
        }

        $phoneNumbers = $this->getConnection()->executeQuery('select phone_column from table_with_phone order by id asc')->fetchFirstColumn();
        $this->assertNotNull($phoneNumbers[0]);
        $this->assertNotSame('0234567834', $phoneNumbers[0]);
        $this->assertNotNull($phoneNumbers[1]);
        $this->assertNotSame('0334567234', $phoneNumbers[1]);
        $this->assertNotNull($phoneNumbers[2]);
        $this->assertNotSame('0534567234', $phoneNumbers[2]);
        $this->assertNull($phoneNumbers[4]);
        $this->assertCount(4, \array_unique($phoneNumbers), 'All generated values are different.');

        $this->assertNull(
            $this->getConnection()->executeQuery('select phone_column from table_with_phone where id = 2')->fetchOne(),
        );
    }
}
