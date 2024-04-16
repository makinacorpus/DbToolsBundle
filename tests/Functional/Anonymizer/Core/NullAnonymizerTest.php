<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;

class NullAnonymizerTest extends FunctionalTestCase
{
    /** @before */
    protected function createTestData(): void
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
    }

    public function testAnonymize(): void
    {
        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'data',
            'null',
            new Options()
        ));

        $this->assertSame(
            "toto1@example.com",
            $this->getDatabaseSession()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();
        $this->assertNull($datas[0]);
        $this->assertNull($datas[1]);
        $this->assertNull($datas[2]);
        $this->assertNull($datas[3]);
    }
}
