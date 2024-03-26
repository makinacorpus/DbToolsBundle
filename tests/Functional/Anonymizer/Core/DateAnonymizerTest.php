<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;

class DateAnonymizerTest extends FunctionalTestCase
{
    /** @before */
    protected function createTestData(): void
    {
        \date_default_timezone_set('UTC');

        $this->createOrReplaceTable(
            'table_test',
            [
                'id' => 'integer',
                'date1' => 'datetime',
                'date2' => 'date',
            ],
            [
                [
                    'id' => '1',
                    'date1' => '1983-03-22 08:25:00',
                    'date2' => '1983-03-22',
                ],
                [
                    'id' => '2',
                    'date1' => '1793-12-31 21:20:00',
                    'date2' => '1793-12-31',
                ],
                [
                    'id' => '3',
                    'date1' => '2178-01-01 21:20:00',
                    'date2' => '2178-01-01',
                ],
                [
                    'id' => '4',
                ],
            ],
        );
    }

    public function testAnonymizeWithRangeAsDate(): void
    {
        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'date1',
            'date',
            new Options([
                'min' => '2010-05-01',
                'max' => '2010-08-31',
                'format' => 'datetime',
            ])
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(),
            $config
        );

        $this->assertStringStartsWith(
            "1983-03-22 08:25:00",
            $this->getConnection()->executeQuery('select date1 from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getConnection()->executeQuery('select date1 from table_test order by id asc')->fetchFirstColumn();
        $this->assertNotNull($datas[0]);
        $this->assertGreaterThanOrEqual('2010-05-01 00:00:00', $datas[0]);
        $this->assertLessThanOrEqual('2010-08-31 23:59:59', $datas[0]);
        $this->assertNotNull($datas[1]);
        $this->assertGreaterThanOrEqual('2010-05-01 00:00:00', $datas[1]);
        $this->assertLessThanOrEqual('2010-08-31 23:59:59', $datas[1]);
        $this->assertNotNull($datas[2]);
        $this->assertGreaterThanOrEqual('2010-05-01 00:00:00', $datas[2]);
        $this->assertLessThanOrEqual('2010-08-31 23:59:59', $datas[2]);
        $this->assertNull($datas[3]);
        $this->assertCount(4, \array_unique($datas), 'All generated values are different.');
    }

    /**
     * Test with a date range exceeding 68 years (max int in seconds).
     */
    public function testAnonymizeWithRangeAsDateHuge(): void
    {
        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'date1',
            'date',
            new Options([
                'min' => '1710-01-01',
                'max' => '2560-03-31',
                'format' => 'date',
            ])
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(),
            $config
        );

        $this->assertStringStartsWith(
            "1983-03-22 08:25:00",
            $this->getConnection()->executeQuery('select date1 from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getConnection()->executeQuery('select date1 from table_test order by id asc')->fetchFirstColumn();
        $this->assertNotNull($datas[0]);
        $this->assertGreaterThanOrEqual('1710-01-01 00:00:00', $datas[0]);
        $this->assertLessThanOrEqual('2560-03-31 23:59:59', $datas[0]);
        $this->assertNotNull($datas[1]);
        $this->assertGreaterThanOrEqual('1710-01-01 00:00:00', $datas[1]);
        $this->assertLessThanOrEqual('2560-03-31 23:59:59', $datas[1]);
        $this->assertNotNull($datas[2]);
        $this->assertGreaterThanOrEqual('1710-01-01 00:00:00', $datas[2]);
        $this->assertLessThanOrEqual('2560-03-31 23:59:59', $datas[2]);
        $this->assertNull($datas[3]);
        $this->assertCount(4, \array_unique($datas), 'All generated values are different.');
    }

    public function testAnonymizeWithRangeAsDateTime(): void
    {
        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'date1',
            'date',
            new Options([
                'min' => '2010-05-01 12:30:00',
                'max' => '2010-08-31 18:25:00',
                'format' => 'datetime',
            ])
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(),
            $config
        );

        $this->assertStringStartsWith(
            "1983-03-22 08:25:00",
            $this->getConnection()->executeQuery('select date1 from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getConnection()->executeQuery('select date1 from table_test order by id asc')->fetchFirstColumn();
        $this->assertNotNull($datas[0]);
        $this->assertGreaterThanOrEqual('2010-05-01 12:30:00', $datas[0]);
        $this->assertLessThanOrEqual('2010-08-31 18:25:00', $datas[0]);
        $this->assertNotNull($datas[1]);
        $this->assertGreaterThanOrEqual('2010-05-01 12:30:00', $datas[1]);
        $this->assertLessThanOrEqual('2010-08-31 18:25:00', $datas[1]);
        $this->assertNotNull($datas[2]);
        $this->assertGreaterThanOrEqual('2010-05-01 12:30:00', $datas[2]);
        $this->assertLessThanOrEqual('2010-08-31 18:25:00', $datas[2]);
        $this->assertNull($datas[3]);
        $this->assertCount(4, \array_unique($datas), 'All generated values are different.');
    }

    /**
     * Test with a date range exceeding 68 years (max int in seconds).
     */
    public function testAnonymizeWithRangeAsDateTimeHuge(): void
    {
        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'date1',
            'date',
            new Options([
                'min' => '1700-01-01',
                'max' => '2100-12-31',
                'format' => 'datetime',
            ])
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(),
            $config
        );

        $this->assertStringStartsWith(
            "1983-03-22 08:25:00",
            $this->getConnection()->executeQuery('select date1 from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getConnection()->executeQuery('select date1 from table_test order by id asc')->fetchFirstColumn();
        $this->assertNotNull($datas[0]);
        $this->assertGreaterThanOrEqual('1700-01-01 00:00:00', $datas[0]);
        $this->assertLessThanOrEqual('2100-12-31 23:59:59', $datas[0]);
        $this->assertNotNull($datas[1]);
        $this->assertGreaterThanOrEqual('1700-01-01 00:00:00', $datas[1]);
        $this->assertLessThanOrEqual('2100-12-31 23:59:59', $datas[1]);
        $this->assertNotNull($datas[2]);
        $this->assertGreaterThanOrEqual('1700-01-01 00:00:00', $datas[2]);
        $this->assertLessThanOrEqual('2100-12-31 23:59:59', $datas[2]);
        $this->assertNull($datas[3]);
        $this->assertCount(4, \array_unique($datas), 'All generated values are different.');
    }

    public function testAnonymizeWithDeltaAsDate(): void
    {
        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'date2',
            'date',
            new Options([
                'delta' => '1 month 6 days 4 hour',
                'format' => 'date',
            ])
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(),
            $config
        );

        $this->assertStringStartsWith(
            "1983-03-22",
            $this->getConnection()->executeQuery('select date2 from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getConnection()->executeQuery('select date2 from table_test order by id asc')->fetchFirstColumn();
        $this->assertNotNull($datas[0]);
        $this->assertGreaterThanOrEqual('1983-02-14', $datas[0]);
        $this->assertLessThanOrEqual('1983-04-30', $datas[0]);
        $this->assertNotNull($datas[1]);
        $this->assertGreaterThanOrEqual('1793-11-23', $datas[1]);
        $this->assertLessThanOrEqual('1794-02-09', $datas[1]);
        $this->assertNotNull($datas[2]);
        $this->assertGreaterThanOrEqual('2177-11-23', $datas[2]);
        $this->assertLessThanOrEqual('2178-02-10', $datas[2]);
        $this->assertNull($datas[3]);
        $this->assertCount(4, \array_unique($datas), 'All generated values are different.');
    }

    public function testAnonymizeWithDeltaAsDateTime(): void
    {
        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'date1',
            'date',
            new Options([
                'delta' => '1 month 6 days 4 hour',
                'format' => 'datetime',
            ])
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(),
            $config
        );

        $this->assertStringStartsWith(
            "1983-03-22 08:25:00",
            $this->getConnection()->executeQuery('select date1 from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getConnection()->executeQuery('select date1 from table_test order by id asc')->fetchFirstColumn();
        $this->assertNotNull($datas[0]);
        $this->assertGreaterThanOrEqual('1983-02-14 04:25:00', $datas[0]);
        $this->assertLessThanOrEqual('1983-04-28 12:25:00', $datas[0]);
        $this->assertNotNull($datas[1]);
        $this->assertGreaterThanOrEqual('1793-11-25 16:20:00', $datas[1]);
        $this->assertLessThanOrEqual('1794-02-07 01:20:00', $datas[1]);
        $this->assertNotNull($datas[2]);
        $this->assertGreaterThanOrEqual('2177-11-25 17:20:00', $datas[2]);
        $this->assertLessThanOrEqual('2178-02-08 01:20:00', $datas[2]);
        $this->assertNull($datas[3]);
        $this->assertCount(4, \array_unique($datas), 'All generated values are different.');
    }
}
