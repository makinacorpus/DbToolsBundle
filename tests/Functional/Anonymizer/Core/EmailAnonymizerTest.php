<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;

class EmailAnonymizerTest extends FunctionalTestCase
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
            'email',
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

        $anonymizator->anonymize();

        $datas = $this->getConnection()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();
        $this->assertNotNull($datas[0]);
        $this->assertNotSame('toto1@example.com', $datas[0]);
        $this->assertStringEndsWith('example.com', $datas[0]);
        $this->assertNotNull($datas[1]);
        $this->assertNotSame('toto2@example.com', $datas[1]);
        $this->assertStringEndsWith('example.com', $datas[0]);
        $this->assertNotNull($datas[2]);
        $this->assertNotSame('toto3@example.com', $datas[2]);
        $this->assertStringEndsWith('example.com', $datas[0]);
        $this->assertNull($datas[3]);
        $this->assertCount(4, \array_unique($datas), 'All generated values are different.');
    }

    public function testAnonymizeWithDomain(): void
    {
        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'data',
            'email',
            new Options(['domain' => 'custom_domain.tld'])
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

        $anonymizator->anonymize();

        $datas = $this->getConnection()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();
        $this->assertNotNull($datas[0]);
        $this->assertNotSame('toto1@example.com', $datas[0]);
        $this->assertStringEndsWith('custom_domain.tld', $datas[0]);
        $this->assertNotNull($datas[1]);
        $this->assertNotSame('toto2@example.com', $datas[1]);
        $this->assertStringEndsWith('custom_domain.tld', $datas[1]);
        $this->assertNotNull($datas[2]);
        $this->assertNotSame('toto3@example.com', $datas[2]);
        $this->assertStringEndsWith('custom_domain.tld', $datas[2]);
        $this->assertNull($datas[3]);
        $this->assertCount(4, \array_unique($datas), 'All generated values are different.');
    }
}
