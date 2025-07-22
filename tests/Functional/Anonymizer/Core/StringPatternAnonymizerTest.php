<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;

class StringPatternAnonymizerTest extends FunctionalTestCase
{
    /** @before */
    protected function createTestData(): void
    {
        $this->createOrReplaceTable(
            'table_test',
            [
                'id' => 'integer',
                'data' => 'text',
            ],
            [
                [
                    'id' => 1,
                    'data' => "Foo value",
                ],
                [
                    'id' => 2,
                    'data' => "Bar value",
                ],
                [
                    'id' => 3,
                    'data' => "Fizz value",
                ],
                [
                    'id' => 4,
                ],
            ],
        );
    }

    public function testAnonymize(): void
    {
        $anonymizator = $this->createAnonymizatorWithConfig(
            new AnonymizerConfig(
                'table_test',
                'data',
                'pattern',
                new Options(['pattern' => "Range [1-1000] for {email} and {address:locality} in {address:country}"])
            ),
        );

        self::assertSame(
            "Foo value",
            (string) $this->getDatabaseSession()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $checkRegex = '/^Range \d+ for [a-z0-9-]+@example\.com and .+ in [\sa-z-]+$/i';

        $data = (string) $datas[0];
        self::assertNotNull($data);
        self::assertNotSame("Foo value", $data);
        self::assertMatchesRegularExpression($checkRegex, $data);

        $data = (string) $datas[1];
        self::assertNotNull($data);
        self::assertNotSame("Baz value", $data);
        self::assertMatchesRegularExpression($checkRegex, $data);

        $data = (string) $datas[2];
        self::assertNotNull($data);
        self::assertNotSame("Fizz value", $data);
        self::assertMatchesRegularExpression($checkRegex, $data);

        self::assertNull($datas[3], 'StringPatternAnonymizer should keep null values');

        self::assertCount(4, \array_unique($datas), 'All generated values are different.');
    }
}
