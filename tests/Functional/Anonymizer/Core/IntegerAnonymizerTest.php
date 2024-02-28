<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;

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
                    'data' => '10',
                ],
                [
                    'id' => '2',
                    'data' => '20',
                ],
                [
                    'id' => '3',
                    'data' => '30',
                ],
                [
                    'id' => '4',
                ],
            ],
        );
    }

    public function testAnonymizeWithMinAndMax(): void
    {
        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'data',
            'integer',
            new Options(['min' => 200, 'max' => 10000])
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(),
            $config
        );

        $this->assertSame(
            10,
            (int) $this->getConnection()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getConnection()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $data = (int) $datas[0];
        $this->assertNotNull($data);
        $this->assertNotSame(10, $data);
        $this->assertGreaterThanOrEqual(200, $data);
        $this->assertLessThanOrEqual(10000, $data);

        $data = (int) $datas[1];
        $this->assertNotNull($data);
        $this->assertNotSame(20, $data);
        $this->assertGreaterThanOrEqual(200, $data);
        $this->assertLessThanOrEqual(10000, $data);

        $data = (int) $datas[2];
        $this->assertNotNull($data);
        $this->assertNotSame(30, $data);
        $this->assertGreaterThanOrEqual(200, $data);
        $this->assertLessThanOrEqual(10000, $data);

        $this->assertNull($datas[3], 'IntegerAnonymizer should keep null values');

        $this->assertCount(4, \array_unique($datas), 'All generated values are different.');
    }

    public function testAnonymizeWithDelta(): void
    {
        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'data',
            'integer',
            new Options(['delta' => 10])
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(),
            $config
        );

        $this->assertSame(
            10,
            (int) $this->getConnection()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        foreach ($anonymizator->anonymize() as $message) {
        }

        $datas = $this->getConnection()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $data = (int) $datas[0];
        $this->assertNotNull($data);
        // Initial value is 10, we added a value in [-10, 10]
        // so current value should be in [10-10, 10+10]
        $this->assertGreaterThanOrEqual(0, $data);
        $this->assertLessThanOrEqual(20, $data);

        $data = (int) $datas[1];
        $this->assertNotNull($data);
        // Initial value is 20, we added a value in [-10, 10]
        // so current value should be in [20-10, 20+10]
        $this->assertGreaterThanOrEqual(10, $data);
        $this->assertLessThanOrEqual(30, $data);

        $data = (int) $datas[2];
        $this->assertNotNull($data);
        // Initial value is 30, we added a value in [-10, 10]
        // so current value should be in [30-10, 30+10]
        $this->assertGreaterThanOrEqual(20, $data);
        $this->assertLessThanOrEqual(40, $data);

        $this->assertNull($datas[3], 'IntegerAnonymizer should keep null values');
    }

    public function testAnonymizeWithPercent(): void
    {
        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'data',
            'integer',
            new Options(['percent' => 50])
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(),
            $config
        );

        $this->assertSame(
            10,
            (int) $this->getConnection()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        foreach ($anonymizator->anonymize() as $message) {
        }

        $datas = $this->getConnection()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $data = (int) $datas[0];
        $this->assertNotNull($data);
        // Initial value is 10, we added a random percent of
        // this value in [-50%, 50%],
        // so current value should be in [10*0.5, 10*1.5]
        $this->assertGreaterThanOrEqual(5, $data);
        $this->assertLessThanOrEqual(15, $data);

        $data = (int) $datas[1];
        $this->assertNotNull($data);
        // Initial value is 20, we added a random percent of
        // this value in [-50%, 50%],
        // so current value should be in [20*0.5, 20*1.5]
        $this->assertGreaterThanOrEqual(10, $data);
        $this->assertLessThanOrEqual(30, $data);

        $data = (int) $datas[2];
        $this->assertNotNull($data);
        // Initial value is 30, we added a random percent of
        // this value in [-50%, 50%],
        // so current value should be in [30*0.5, 30*1.5]
        $this->assertGreaterThanOrEqual(15, $data);
        $this->assertLessThanOrEqual(45, $data);

        $this->assertNull($datas[3], 'IntegerAnonymizer should keep null values');
    }
}
