<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Tests\FunctionalTestCase;

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
                            'my_street',
                            Type::getType(Types::TEXT),
                            [
                                'notnull' => null,
                            ]
                        ),
                        new Column(
                            'my_city',
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
            'fr_fr.address',
            new Options([
                'street_address' => 'my_street',
                'locality' => 'my_city',
            ]),
        ));

        $anonymizator = new Anonymizator($this->getConnection(), new AnonymizerRegistry(), $config);
        $anonymizator->addAnonymizerIdColumn('table_test');

        foreach ($anonymizator->anonymize() as $message) {
        }

        self::expectNotToPerformAssertions();
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
        $anonymizator->addAnonymizerIdColumn('table_test');

        self::assertSame(
            [
                ['id' => 1, 'value' => 'foo', AbstractAnonymizer::JOIN_ID => 1],
                ['id' => 2, 'value' => 'bar', AbstractAnonymizer::JOIN_ID => 2],
                ['id' => 3, 'value' => 'baz', AbstractAnonymizer::JOIN_ID => 3],
            ],
            $this
                ->getConnection()
                ->executeQuery(
                    'select id, value, _db_tools_id from table_test order by id'
                )
                ->fetchAllAssociative()
        );
    }
}
