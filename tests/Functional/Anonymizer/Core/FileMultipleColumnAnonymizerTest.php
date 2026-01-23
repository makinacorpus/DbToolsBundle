<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;

class FileMultipleColumnAnonymizerTest extends FunctionalTestCase
{
    /** @before */
    protected function createTestData(): void
    {
        $this->createOrReplaceTable(
            'table_test',
            [
                'id' => 'integer',
                'column1' => 'string',
                'column2' => 'string',
            ],
            [
                [
                    'id' => 1,
                    'column1' => 'test1',
                    'column2' => 'test1',
                ],
                [
                    'id' => 2,
                    'column1' => 'test2',
                    'column2' => 'test2',
                ],
                [
                    'id' => 3,
                    'column1' => 'test3',
                    'column2' => 'test3',
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
            'file_column',
            new Options([
                'source' => \dirname(__DIR__, 3) . '/Resources/Anonymization/Pack/resources/enum-file.csv',
                'columns' => ['pif', null, 'pouf'],
                'pif' => 'column1',
                'pouf' => 'column2',
            ])
        ));

        // Values from CSV.
        $samplePaf = ['foo', 'a', '1', 'cat'];
        $samplePouf = ['baz', 'c', '3', 'girafe'];

        self::assertSame(
            "test1",
            $this->getDatabaseSession()->executeQuery('select column1 from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select * from table_test order by id asc')->fetchAllAssociative();

        self::assertNotNull($datas[0]);
        self::assertNotSame('test1', $datas[0]['column1']);
        self::assertNotSame('test1', $datas[0]['column2']);
        self::assertContains($datas[0]['column1'], $samplePaf);
        self::assertContains($datas[0]['column2'], $samplePouf);

        self::assertNotNull($datas[1]);
        self::assertNotSame('test2', $datas[1]['column1']);
        self::assertNotSame('test2', $datas[1]['column2']);
        self::assertContains($datas[1]['column1'], $samplePaf);
        self::assertContains($datas[1]['column2'], $samplePouf);

        self::assertNotNull($datas[2]);
        self::assertNotSame('test3', $datas[2]['column1']);
        self::assertNotSame('test3', $datas[2]['column2']);
        self::assertContains($datas[2]['column1'], $samplePaf);
        self::assertContains($datas[2]['column2'], $samplePouf);

        // self::assertNull($datas[3]['column1']);
        // self::assertNull($datas[3]['my_secondary_address']);

        self::assertCount(4, \array_unique(\array_map(fn ($value) => \serialize($value), $datas)), 'All generated values are different.');
    }
}
