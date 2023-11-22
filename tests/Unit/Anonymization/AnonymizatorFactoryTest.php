<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymization\AnonymizatorFactory;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
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
            $this->createMock(AnonymizerRegistry::class)
        );
        $factory->addConfigurationLoader(new TestingAnonymizationLoader());

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

        $factory = new AnonymizatorFactory(
            $doctrineRegistry,
            $this->createMock(AnonymizerRegistry::class),
        );

        $factory->addConfigurationLoader(new TestingAnonymizationLoader($configs = [
            new AnonymizerConfig(
                'table_test',
                'target_test',
                'anonymizer_test',
                new Options()
            ),
            new AnonymizerConfig(
                'table_test',
                'target_test2',
                'anonymizer_test2',
                new Options(['option1' => 'value1'])
            )
        ]));

        $anonymizator = $factory->getOrCreate('connection');

        self::assertInstanceOf(Anonymizator::class, $anonymizator);
        self::assertCount(1, $anonymizator->getAnonymizationConfig()->all());
        self::assertCount(2, $anonymizator->getAnonymizationConfig()->all()['table_test']);
        self::assertContains($configs[0], $anonymizator->getAnonymizationConfig()->all()['table_test']);
        self::assertContains($configs[1], $anonymizator->getAnonymizationConfig()->all()['table_test']);
    }
}
