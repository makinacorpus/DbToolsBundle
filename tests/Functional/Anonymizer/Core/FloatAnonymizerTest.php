<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;
use MakinaCorpus\QueryBuilder\Vendor;

class FloatAnonymizerTest extends FunctionalTestCase
{
    /** @before */
    protected function createTestData(): void
    {
        $this->createOrReplaceTable(
            'table_test',
            [
                'id' => 'integer',
                'data' => 'float',
            ],
            [
                [
                    'id' => 1,
                    'data' => 10.5,
                ],
                [
                    'id' => 2,
                    'data' => 20.5,
                ],
                [
                    'id' => 3,
                    'data' => 30.5,
                ],
                [
                    'id' => 4,
                ],
            ],
        );
    }

    public function testAnonymizeWithMinAndMax(): void
    {
        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'data',
            'float',
            new Options(['min' => 2, 'max' => 5.5, 'precision' => 6])
        ));

        $this->assertSame(
            10.5,
            (float) $this->getDatabaseSession()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $data = (float) $datas[0];
        $this->assertNotSame(10.5, $data);
        $this->assertGreaterThanOrEqual(2, $data);
        $this->assertLessThanOrEqual(5.5, $data);
        // Checking precision
        $this->assertSame(\number_format($data, 6), \number_format(\round($data, 6), 6));

        $data = (float) $datas[1];
        $this->assertNotSame(20.5, $data);
        $this->assertGreaterThanOrEqual(2, $data);
        $this->assertLessThanOrEqual(5.5, $data);
        // Checking precision
        $this->assertSame(\number_format($data, 6), \number_format(\round($data, 6), 6));

        $data = (float) $datas[2];
        $this->assertNotSame(30.5, $data);
        $this->assertGreaterThanOrEqual(2, $data);
        $this->assertLessThanOrEqual(5.5, $data);
        // Checking precision
        $this->assertSame(\number_format($data, 6), \number_format(\round($data, 6), 6));

        $this->assertNull($datas[3]);

        $this->assertCount(4, \array_unique($datas), 'All generated values are different.');
    }

    public function testAnonymizeWithDelta(): void
    {
        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'data',
            'float',
            new Options(['delta' => 5.2, 'precision' => 4])
        ));

        $this->assertSame(
            10.5,
            (float) $this->getDatabaseSession()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $data = (float) $datas[0];
        // Initial value is 10.5, we added a value in [-5.2, 5.2]
        // so current value should be in [10.5-5.2, 10.5+5.2]
        $this->assertGreaterThanOrEqual(5.3, $data);
        $this->assertLessThanOrEqual(15.7, $data);
        // Checking precision
        $this->assertSame(\number_format($data, 6), \number_format(\round($data, 6), 6));

        $data = (float) $datas[1];
        // Initial value is 20.5, we added a value in [-5.2, 5.2]
        // so current value should be in [20.5-5.2, 20.5+5.2]
        $this->assertGreaterThanOrEqual(15.3, $data);
        $this->assertLessThanOrEqual(25.7, $data);
        // Checking precision
        $this->assertSame(\number_format($data, 6), \number_format(\round($data, 6), 6));

        $data = (float) $datas[2];
        // Initial value is 30.5, we added a value in [-5.2, 5.2]
        // so current value should be in [30.5-5.2, 30.5+5.2]
        $this->assertGreaterThanOrEqual(25.3, $data);
        $this->assertLessThanOrEqual(35.7, $data);
        // Checking precision
        $this->assertSame(\number_format($data, 6), \number_format(\round($data, 6), 6));

        $this->assertNull($datas[3]);
    }

    public function testAnonymizeWithPercent(): void
    {
        // @see https://github.com/makinacorpus/DbToolsBundle/issues/210
        $this->skipIfDatabaseLessThan(Vendor::MYSQL, '8', 'A well known issue appears randomly with MYSQL 5.7.');

        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'data',
            'float',
            new Options(['percent' => 5])
        ));

        $this->assertSame(
            10.5,
            (float) $this->getDatabaseSession()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $data = (float) $datas[0];
        // Initial value is 10.5, we added a random percent of
        // this value in [-5%, 5%],
        // so current value should be in [10.5*0.95, 10.5*1.05]
        $this->assertGreaterThanOrEqual(9.975, \round($data, 3));
        $this->assertLessThanOrEqual(11.025, \round($data, 3));

        $data = (float) $datas[1];
        // Initial value is 20.5, we added a random percent of
        // this value in [-5%, 5%],
        // so current value should be in [20.5*0.95, 20.5*1.05]
        $this->assertGreaterThanOrEqual(19.475, \round($data, 3));
        $this->assertLessThanOrEqual(21.525, \round($data, 3));

        $data = (float) $datas[2];
        // Initial value is 30.5, we added a random percent of
        // this value in [-5%, 5%],
        // so current value should be in [30.5*0.95, 30.5*1.05]
        $this->assertGreaterThanOrEqual(28.975, \round($data, 3));
        $this->assertLessThanOrEqual(32.025, \round($data, 3));

        $this->assertNull($datas[3]);
    }
}
