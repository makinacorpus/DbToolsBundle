<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;

class ConstantAnonymizerTest extends FunctionalTestCase
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
                    'data' => 'toto1@example.com',
                ],
                [
                    'id' => '2',
                    'data' => 'toto2@example.com',
                ],
                [
                    'id' => '3',
                    'data' => 'toto3@example.com',
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
            'constant',
            new Options()
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(),
            $config
        );

        $this->assertSame(
            "toto1@example.com",
            $this->getConnection()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        foreach ($anonymizator->anonymize() as $message) {
        }

        $datas = $this->getConnection()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $this->assertSame('xxxxxx', $datas[0]);
        $this->assertSame('xxxxxx', $datas[1]);
        $this->assertSame('xxxxxx', $datas[2]);
        $this->assertNull($datas[3]);
    }

    public function testAnonymizeWithCustomValue(): void
    {
        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'data',
            'constant',
            new Options(['value' => 'testtesttest'])
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(),
            $config
        );

        $this->assertSame(
            "toto1@example.com",
            $this->getConnection()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        foreach ($anonymizator->anonymize() as $message) {
        }

        $datas = $this->getConnection()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $this->assertSame('testtesttest', $datas[0]);
        $this->assertSame('testtesttest', $datas[1]);
        $this->assertSame('testtesttest', $datas[2]);
        $this->assertNull($datas[3]);
    }
}
