<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;
use MakinaCorpus\QueryBuilder\Type\Type;
use MakinaCorpus\QueryBuilder\Vendor;

class AnonymizatorTest extends FunctionalTestCase
{
    /** @before */
    protected function createTestData(): void
    {
        $this->dropTableIfExist('table_test');

        $session = $this->getDatabaseSession();

        $session
            ->getSchemaManager()
            ->modify()
            ->createTable('table_test')
                ->column(name: 'id', type: Type::identity(), nullable: false)
                ->column(name: 'value', type: Type::text(), nullable: true)
                ->column(name: 'my_iban', type: Type::text(), nullable: true)
                ->column(name: 'my_bic', type: Type::text(), nullable: true)
                ->primaryKey(['id'])
            ->endTable()
            ->commit()
        ;

        $session
            ->insert('table_test')
            ->values(['value' => 'foo'])
            ->values(['value' => 'bar'])
            ->values(['value' => 'baz'])
            ->executeStatement()
        ;
    }

    public function testMultipleAnonymizersAtOnce(): void
    {
        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'value',
            'string',
            new Options([
                'sample' => ['foo', 'bar', 'baz'],
            ]),
        ));
        $config->add(new AnonymizerConfig(
            'table_test',
            'foo',
            'iban-bic',
            new Options([
                'iban' => 'my_iban',
                'bic' => 'my_bic',
            ]),
        ));

        $anonymizator = new Anonymizator($this->getDatabaseSession(), new AnonymizerRegistry(), $config);
        $anonymizator->addAnonymizerIdColumn('table_test');
        $anonymizator->anonymize();

        self::expectNotToPerformAssertions();
    }

    public function testSerial(): void
    {
        // Some connectors will return string values for int.
        $actual = \array_map(
            fn (array $item) => ['id' => (int) $item['id']] + $item,
            $this
                ->getDatabaseSession()
                ->executeQuery(
                    'select id, value from table_test order by id'
                )
                ->fetchAllAssociative(),
        );

        self::assertSame(
            [
                ['id' => 1, 'value' => 'foo'],
                ['id' => 2, 'value' => 'bar'],
                ['id' => 3, 'value' => 'baz'],
            ],
            $actual,
        );

        $anonymizator = new Anonymizator($this->getDatabaseSession(), new AnonymizerRegistry(), new AnonymizationConfig());
        $anonymizator->addAnonymizerIdColumn('table_test');

        if (Vendor::SQLITE === $this->getDatabaseSession()->getVendorName()) {
            $query = 'select id, value, rowid as _db_tools_id from table_test order by id';
        } else {
            $query = 'select id, value, _db_tools_id from table_test order by id';
        }

        // Some connectors will return string values for int.
        $actual = \array_map(
            fn (array $item) => [
                'id' => (int) $item['id'],
                'value' => $item['value'],
                AbstractAnonymizer::JOIN_ID => (int) $item[AbstractAnonymizer::JOIN_ID],
            ],
            $this->getDatabaseSession()->executeQuery($query)->fetchAllAssociative(),
        );

        self::assertSame(
            [
                ['id' => 1, 'value' => 'foo', AbstractAnonymizer::JOIN_ID => 1],
                ['id' => 2, 'value' => 'bar', AbstractAnonymizer::JOIN_ID => 2],
                ['id' => 3, 'value' => 'baz', AbstractAnonymizer::JOIN_ID => 3],
            ],
            $actual
        );
    }
}
