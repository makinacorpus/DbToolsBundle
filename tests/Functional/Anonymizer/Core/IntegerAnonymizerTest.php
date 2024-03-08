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

        foreach ($anonymizator->anonymize() as $message) {
        }

        $datas = $this->getConnection()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $data = (int) $datas[0];
        $this->assertNotNull($data);
        $this->assertNotSame(1, $data);
        $this->assertTrue($data >= 200 && $data <= 10000);

        $data = (int) $datas[1];
        $this->assertNotNull($data);
        $this->assertNotSame(2, $data);
        $this->assertTrue($data >= 200 && $data <= 10000);

        $data = (int) $datas[2];
        $this->assertNotNull($data);
        $this->assertNotSame(3, $data);
        $this->assertTrue($data >= 200 && $data <= 10000);

        $this->assertNull($datas[3]);

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
        $this->assertNotSame(1, $data);
        $this->assertGreaterThanOrEqual(0, $data);
        $this->assertLessThanOrEqual(20, $data);

        $data = (int) $datas[1];
        $this->assertNotNull($data);
        $this->assertNotSame(2, $data);
        $this->assertGreaterThanOrEqual(10, $data);
        $this->assertLessThanOrEqual(30, $data);

        $data = (int) $datas[2];
        $this->assertNotNull($data);
        $this->assertNotSame(3, $data);
        $this->assertGreaterThanOrEqual(20, $data);
        $this->assertLessThanOrEqual(40, $data);

        $this->assertNull($datas[3]);

        $this->assertCount(4, \array_unique($datas), 'All generated values are different.');
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
        $this->assertNotSame(1, $data);
        $this->assertGreaterThanOrEqual(5, $data);
        $this->assertLessThanOrEqual(15, $data);

        $data = (int) $datas[1];
        $this->assertNotNull($data);
        $this->assertNotSame(2, $data);
        $this->assertGreaterThanOrEqual(10, $data);
        $this->assertLessThanOrEqual(30, $data);

        $data = (int) $datas[2];
        $this->assertNotNull($data);
        $this->assertNotSame(3, $data);
        $this->assertGreaterThanOrEqual(15, $data);
        $this->assertLessThanOrEqual(45, $data);

        $this->assertNull($datas[3]);

        $this->assertCount(4, \array_unique($datas), 'All generated values are different.');
    }
}
