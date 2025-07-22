<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;

class FileEnumAnonymizerTest extends FunctionalTestCase
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
                    'data' => 'test1',
                ],
                [
                    'id' => 2,
                    'data' => 'test2',
                ],
                [
                    'id' => 3,
                    'data' => 'test3',
                ],
                [
                    'id' => 4,
                ],
            ],
        );
    }

    public function testAnonymize(): void
    {
        // File contents.
        $sample = ['foo', 'a', '1'];

        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'data',
            'file_enum',
            new Options(['source' => \dirname(__DIR__, 3) . '/Resources/Anonymization/Pack/resources/enum-file.txt'])
        ));

        self::assertSame(
            'test1',
            $this->getDatabaseSession()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $data = $datas[0];
        self::assertNotNull($data);
        self::assertNotSame('test1', $data);
        self::assertContains($data, $sample);

        $data = $datas[1];
        self::assertNotNull($data);
        self::assertNotSame('test2', $data);
        self::assertContains($data, $sample);

        $data = $datas[2];
        self::assertNotNull($data);
        self::assertNotSame('test3', $data);
        self::assertContains($data, $sample);

        self::assertNull($datas[3]);

        self::assertGreaterThan(1, \array_unique($datas), 'All generated values are different.');
    }

    public function testAnonymizeWithBasePath(): void
    {
        // File contents.
        $sample = ['foo', 'a', '1'];

        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'data',
            'file_enum',
            new Options([
                // In tests, base path is '/var/www' because it's set to \getcwd()
                // per default, which is docker workdir.
                'source' => './tests/Resources/Anonymization/Pack/resources/enum-file.txt',
            ]),
        ));

        self::assertSame(
            'test1',
            $this->getDatabaseSession()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $data = $datas[0];
        self::assertNotNull($data);
        self::assertNotSame('test1', $data);
        self::assertContains($data, $sample);

        $data = $datas[1];
        self::assertNotNull($data);
        self::assertNotSame('test2', $data);
        self::assertContains($data, $sample);

        $data = $datas[2];
        self::assertNotNull($data);
        self::assertNotSame('test3', $data);
        self::assertContains($data, $sample);

        self::assertNull($datas[3]);

        self::assertGreaterThan(1, \array_unique($datas), 'All generated values are different.');
    }
}
