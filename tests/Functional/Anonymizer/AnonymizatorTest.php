<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use MakinaCorpus\DbToolsBundle\Anonymizer\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Tests\FunctionalTestCase;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationConfig;

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
                        ),
                    ]
                ))->setPrimaryKey(['id'])
            )
        ;

        $builder = $connection->createQueryBuilder();
        $builder->insert('table_test')->values(['value' => "'foo'"])->executeStatement();
        $builder->insert('table_test')->values(['value' => "'bar'"])->executeStatement();
        $builder->insert('table_test')->values(['value' => "'baz'"])->executeStatement();
    }

    public function testSerial(): void
    {
        self::assertSame(
            [
                ['id' => 1, 'value' => 'foo'],
                ['id' => 2, 'value' => 'bar'],
                ['id' => 3, 'value' => 'baz'],
            ],
            $this
                ->getConnection()
                ->executeQuery(
                    'select id, value from table_test order by id'
                )
                ->fetchAllAssociative()
        );

        $anonymizator = new Anonymizator($this->getConnection(), new AnonymizerRegistry(), new AnonymizationConfig());
        $anonymizator->addSerialColumn('table_test');

        self::assertSame(
            [
                ['id' => 1, 'value' => 'foo', '_anonymizer_id' => 1],
                ['id' => 2, 'value' => 'bar', '_anonymizer_id' => 2],
                ['id' => 3, 'value' => 'baz', '_anonymizer_id' => 3],
            ],
            $this
                ->getConnection()
                ->executeQuery(
                    'select id, value, _anonymizer_id from table_test order by id'
                )
                ->fetchAllAssociative()
        );
    }
}
