<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Tests\FunctionalTestCase;

class Md5AnonymizerTest extends FunctionalTestCase
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
                    'id' => '1',
                    'data' => "'test1'",
                ],
                [
                    'id' => '2',
                    'data' => "'test2'",
                ],
                [
                    'id' => '3',
                    'data' => "'test3'",
                ],
                [
                    'id' => '4',
                ],
            ],
        );
    }

    public function testAnonymize(): void
    {
        $sample = ['sample1', 'sample2', 'sample3', 'sample4', 'sample5'];

        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'data',
            'md5',
            new Options(['sample' => $sample])
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(),
            $config
        );

        $this->assertSame(
            'test1',
            $this->getConnection()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        foreach ($anonymizator->anonymize() as $message) {
        }

        $datas = $this->getConnection()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $data = $datas[0];
        $this->assertNotNull($data);
        $this->assertNotSame(1, $data);
        $this->assertEquals(32, \strlen($data));

        $data = $datas[1];
        $this->assertNotNull($data);
        $this->assertNotSame(2, $data);
        $this->assertEquals(32, \strlen($data));

        $data = $datas[2];
        $this->assertNotNull($data);
        $this->assertNotSame(3, $data);
        $this->assertEquals(32, \strlen($data));

        $this->assertNull($datas[3]);

        $this->assertCount(4, \array_unique($datas), 'All generated values are different.');
    }
}
