<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Configuration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\Loader\AttributesLoader;
use MakinaCorpus\DbToolsBundle\Tests\Resources\Loader\TestEntity;
use MakinaCorpus\DbToolsBundle\Test\UnitTestCase;
use MakinaCorpus\DbToolsBundle\Tests\Resources\Loader\TestEntityWithEmbedded;

class AttributeLoaderTest extends UnitTestCase
{
    public function testLoadOk(): void
    {
        $attributeDriver = new AttributeDriver([
            \dirname(\dirname(\dirname(__DIR__))) . '/Resources/Loader'
        ]);
        $classMetadata = new ClassMetadata(TestEntity::class);
        $classMetadata->initializeReflection(new RuntimeReflectionService());

        $attributeDriver->loadMetadataForClass(TestEntity::class, $classMetadata);

        $metaDataFactory = $this->createMock(ClassMetadataFactory::class);
        $metaDataFactory
            ->expects($this->exactly(1))
            ->method('getAllMetadata')
            ->willReturn([$classMetadata])
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->exactly(1))
            ->method('getMetadataFactory')
            ->willReturn($metaDataFactory)
        ;

        $entityManagerProvider = $this->createMock(EntityManagerProvider::class);
        $entityManagerProvider
            ->expects($this->exactly(1))
            ->method('getManager')
            ->willReturn($entityManager)
        ;

        // We try to load configuration for the 'default' connection.
        $config = new AnonymizationConfig('default');
        (new AttributesLoader($entityManagerProvider))->load($config);

        // Then we validate what's in it:
        self::assertCount(1, $config->all());

        $testTableConfig = $config->getTableConfig('test');
        self::assertCount(3, $testTableConfig);

        self::assertInstanceOf(AnonymizerConfig::class, $testTableConfig['age']);
        self::assertSame('integer', $testTableConfig['age']->anonymizer);
        self::assertSame('age', $testTableConfig['age']->targetName);
        self::assertSame(0, $testTableConfig['age']->options->get('min'));
        self::assertSame(65, $testTableConfig['age']->options->get('max'));

        self::assertInstanceOf(AnonymizerConfig::class, $testTableConfig['email']);
        self::assertSame('email', $testTableConfig['email']->anonymizer);
        self::assertSame('email', $testTableConfig['email']->targetName);
        self::assertSame('toto.com', $testTableConfig['email']->options->get('domain'));

        self::assertInstanceOf(AnonymizerConfig::class, $testTableConfig['address_0']);
        self::assertSame('address', $testTableConfig['address_0']->anonymizer);
        self::assertSame('address_0', $testTableConfig['address_0']->targetName);
        self::assertSame('street', $testTableConfig['address_0']->options->get('street_address'));
        self::assertNull($testTableConfig['address_0']->options->get('secondary_address'));
        self::assertSame('zip_code', $testTableConfig['address_0']->options->get('postal_code'));
        self::assertSame('city', $testTableConfig['address_0']->options->get('locality'));
        self::assertNull($testTableConfig['address_0']->options->get('region'));
        self::assertSame('country', $testTableConfig['address_0']->options->get('country'));
    }

    public function testLoadIgnoreEmbedded(): void
    {
        $attributeDriver = new AttributeDriver([
            \dirname(\dirname(\dirname(__DIR__))) . '/Resources/Loader'
        ]);
        $classMetadata = new ClassMetadata(TestEntityWithEmbedded::class);
        $classMetadata->initializeReflection(new RuntimeReflectionService());

        $attributeDriver->loadMetadataForClass(TestEntity::class, $classMetadata);

        $metaDataFactory = $this->createMock(ClassMetadataFactory::class);
        $metaDataFactory
            ->expects($this->exactly(1))
            ->method('getAllMetadata')
            ->willReturn([$classMetadata])
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->exactly(1))
            ->method('getMetadataFactory')
            ->willReturn($metaDataFactory)
        ;

        $entityManagerProvider = $this->createMock(EntityManagerProvider::class);
        $entityManagerProvider
            ->expects($this->exactly(1))
            ->method('getManager')
            ->willReturn($entityManager)
        ;

        // We try to load configuration for the 'default' connection.
        $config = new AnonymizationConfig('default');
        (new AttributesLoader($entityManagerProvider))->load($config);

        // Then we validate that there is still nothing in the config:
        // (it means that the embeddable has been correctly ignored)
        self::assertCount(0, $config->all());
    }
}
