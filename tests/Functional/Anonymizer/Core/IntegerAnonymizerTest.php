<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Tests\FunctionalTestCase;

class IntegerAnonymizerTest extends FunctionalTestCase
{
    /** @before */
    protected function createTestData(): void
    {
        $this->createOrReplaceTable(
            'table_test',
            [
                'id' => 'integer',
                'data' => 'integer',
            ],
            [
                [
                    'id' => '1',
                    'data' => '1',
                ],
                [
                    'id' => '2',
                    'data' => '2',
                ],
                [
                    'id' => '3',
                    'data' => '3',
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
            'table_test',
            'data',
            'integer',
            new Options(['min' => 200, 'max' => 556])
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(''),
            $config
        );

        $this->assertSame(
            1,
            (int) $this->getConnection()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        foreach ($anonymizator->anonymize() as $message) {
        }

        $datas = $this->getConnection()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $data = (int) $datas[0];
        $this->assertNotNull($data);
        $this->assertNotSame(1, $data);
        $this->assertTrue($data >= 200 && $data <= 556);

        $data = (int) $datas[1];
        $this->assertNotNull($data);
        $this->assertNotSame(2, $data);
        $this->assertTrue($data >= 200 && $data <= 556);

        $data = (int) $datas[2];
        $this->assertNotNull($data);
        $this->assertNotSame(3, $data);
        $this->assertTrue($data >= 200 && $data <= 556);

        $this->assertNull($datas[3]);

        $this->assertCount(4, \array_unique($datas), 'All generated values are different.');
    }
}
