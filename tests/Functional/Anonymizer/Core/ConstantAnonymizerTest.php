<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;

class ConstantAnonymizerTest extends FunctionalTestCase
{
    public function testAnonymize(): void
    {
        $this->createOrReplaceTable(
            'table_test',
            [
                'id' => 'integer',
                'data' => 'string',
            ],
            [
                [
                    'id' => 1,
                    'data' => 'toto1@example.com',
                ],
                [
                    'id' => 2,
                    'data' => 'toto2@example.com',
                ],
                [
                    'id' => 3,
                    'data' => 'toto3@example.com',
                ],
                [
                    'id' => 4,
                ],
            ],
        );

        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'data',
            'constant',
            new Options(['value' => 'xxxxxx'])
        ));

        $this->assertSame(
            "toto1@example.com",
            $this->getDatabaseSession()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $this->assertSame('xxxxxx', $datas[0]);
        $this->assertSame('xxxxxx', $datas[1]);
        $this->assertSame('xxxxxx', $datas[2]);
        $this->assertNull($datas[3]);
    }

    public function testAnonymizeWithCustomType(): void
    {
        $this->createOrReplaceTable(
            'table_test',
            [
                'id' => 'integer',
                'data' => 'integer',
            ],
            [
                [
                    'id' => 1,
                    'data' => 52,
                ],
                [
                    'id' => 2,
                    'data' => 76,
                ],
                [
                    'id' => 3,
                    'data' => 89,
                ],
                [
                    'id' => 4,
                ],
            ],
        );

        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'data',
            'constant',
            new Options(['value' => '2012', 'type' => 'integer'])
        ));

        $this->assertSame(
            52,
            (int) $this->getDatabaseSession()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $this->assertSame(2012, (int) $datas[0]);
        $this->assertSame(2012, (int) $datas[1]);
        $this->assertSame(2012, (int) $datas[2]);
        $this->assertNull($datas[3]);
    }
}
