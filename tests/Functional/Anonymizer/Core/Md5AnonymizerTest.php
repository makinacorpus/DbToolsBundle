<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;
use MakinaCorpus\QueryBuilder\Platform;

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
                    'data' => 'test1',
                ],
                [
                    'id' => '2',
                    'data' => 'test2',
                ],
                [
                    'id' => '3',
                    'data' => 'test3',
                ],
                [
                    'id' => '4',
                ],
            ],
        );
    }

    public function testAnonymize(): void
    {
        $this->skipIfDatabase(Platform::SQLITE, 'SQLite does not implement MD5() neither any other hash function.');

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

        $anonymizator->anonymize();

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
