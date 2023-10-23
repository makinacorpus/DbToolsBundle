<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizatorFactory;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Tests\Mock\TestingAnonymizationLoader;
use MakinaCorpus\DbToolsBundle\Tests\UnitTestCase;

class AnonymizatorFactoryTest extends UnitTestCase
{
    public function testGetOrCreateWithEmptyConfig(): void
    {
        $entityManager = $this->createMock(Connection::class);

        $doctrineRegistry = $this->createMock(ManagerRegistry::class);
        $doctrineRegistry
            ->expects($this->exactly(1))
            ->method('getConnection')
            ->willReturn($entityManager)
        ;

        $factory = new AnonymizatorFactory(
            $doctrineRegistry,
            $this->createMock(AnonymizerRegistry::class),
            new TestingAnonymizationLoader()
        );

        $anonymizator = $factory->getOrCreate('connection');

        self::assertInstanceOf(Anonymizator::class, $anonymizator);
        self::assertCount(0, $anonymizator->getAnonymizationConfig()->all());

        $doctrineRegistry
            ->expects($this->exactly(1))
            ->method('getConnections')
            ->willReturn([])
        ;

        self::assertCount(0, $factory->all());
    }

    public function testGetOrCreateWithConfig(): void
    {
        $entityManager = $this->createMock(Connection::class);

        $doctrineRegistry = $this->createMock(ManagerRegistry::class);
        $doctrineRegistry
            ->expects($this->exactly(1))
            ->method('getConnection')
            ->willReturn($entityManager)
        ;

        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'target_test',
            'anonymizer_test',
            new Options()
        ));
        $config->add(new AnonymizerConfig(
            'table_test',
            'target_test2',
            'anonymizer_test2',
            new Options(['option1' => 'value1'])
        ));

        $factory = new AnonymizatorFactory(
            $doctrineRegistry,
            $this->createMock(AnonymizerRegistry::class),
            new TestingAnonymizationLoader($config)
        );

        $anonymizator = $factory->getOrCreate('connection');

        self::assertInstanceOf(Anonymizator::class, $anonymizator);
        self::assertCount(1, $anonymizator->getAnonymizationConfig()->all());
        self::assertSame($config, $anonymizator->getAnonymizationConfig());
    }
}