<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymization\AnonymizatorFactory;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Database\DatabaseSessionRegistry;
use MakinaCorpus\DbToolsBundle\Test\UnitTestCase;
use MakinaCorpus\DbToolsBundle\Tests\Mock\TestingAnonymizationLoader;

class AnonymizatorFactoryTest extends UnitTestCase
{
    public function testGetOrCreateWithEmptyConfig(): void
    {
        $databaseSessionRegistry = $this->createMock(DatabaseSessionRegistry::class);
        $databaseSessionRegistry
            ->expects($this->exactly(1))
            ->method('getDatabaseSession')
            ->willReturn($this->getDatabaseSession())
        ;

        $factory = new AnonymizatorFactory(
            $databaseSessionRegistry,
            $this->createMock(AnonymizerRegistry::class)
        );
        $factory->addConfigurationLoader(new TestingAnonymizationLoader());

        $anonymizator = $factory->getOrCreate('connection');

        self::assertInstanceOf(Anonymizator::class, $anonymizator);
        self::assertCount(0, $anonymizator->getAnonymizationConfig()->all());

        $databaseSessionRegistry
            ->expects($this->exactly(1))
            ->method('getConnectionNames')
            ->willReturn([])
        ;

        self::assertCount(0, $factory->all());
    }

    public function testGetOrCreateWithConfig(): void
    {
        $databaseSessionRegistry = $this->createMock(DatabaseSessionRegistry::class);
        $databaseSessionRegistry
            ->expects($this->exactly(1))
            ->method('getDatabaseSession')
            ->willReturn($this->getDatabaseSession())
        ;

        $factory = new AnonymizatorFactory(
            $databaseSessionRegistry,
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
