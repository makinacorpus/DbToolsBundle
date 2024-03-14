<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;

class AnonymizatorTest extends FunctionalTestCase
{
    /** @before */
    protected function createTestData(): void
    {
        $this->dropTableIfExist('table_test');

        $connection = $this->getConnection();
        $connection
            ->createSchemaManager()
            ->createTable(
                (new Table(
                    'table_test',
                    [
                        new Column(
                            'id',
                            Type::getType(Types::BIGINT),
                            [
                                'autoincrement' => true,
                            ]
                        ),
                        new Column(
                            'value',
                            Type::getType(Types::TEXT),
                            [
                                'notnull' => null,
                            ]
                        ),
                        new Column(
                            'my_iban',
                            Type::getType(Types::TEXT),
                            [
                                'notnull' => null,
                            ]
                        ),
                        new Column(
                            'my_bic',
                            Type::getType(Types::TEXT),
                            [
                                'notnull' => null,
                            ]
                        ),
                    ],
                ))->setPrimaryKey(['id'])
            )
        ;

        $builder = $connection->createQueryBuilder();
        $builder->insert('table_test')->values(['value' => "'foo'"])->executeStatement();
        $builder->insert('table_test')->values(['value' => "'bar'"])->executeStatement();
        $builder->insert('table_test')->values(['value' => "'baz'"])->executeStatement();
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

        $anonymizator = new Anonymizator($this->getConnection(), new AnonymizerRegistry(), $config);
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
                ->getConnection()
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

        $anonymizator = new Anonymizator($this->getConnection(), new AnonymizerRegistry(), new AnonymizationConfig());
        $anonymizator->addAnonymizerIdColumn('table_test');

        $platform = $this->getConnection()->getDatabasePlatform();

        if ($platform instanceof SqlitePlatform) {
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
            $this->getConnection()->executeQuery($query)->fetchAllAssociative(),
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
