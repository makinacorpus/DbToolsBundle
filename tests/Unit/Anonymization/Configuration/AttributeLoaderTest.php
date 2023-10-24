<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Configuration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\Loader\AttributesLoader;
use MakinaCorpus\DbToolsBundle\Tests\Resources\Loader\TestEntity;
use MakinaCorpus\DbToolsBundle\Tests\UnitTestCase;

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
        (new AttributesLoader($entityManagerProvider))->loadTo($config);

        // Then we validate what's in it:
        self::assertCount(1, $config->all());

        $userTableConfig = $config->getTableConfig('test');
        self::assertCount(3, $userTableConfig);

        self::assertInstanceOf(AnonymizerConfig::class, $userTableConfig['age']);
        self::assertSame('integer', $userTableConfig['age']->anonymizer);
        self::assertSame('age', $userTableConfig['age']->targetName);
        self::assertSame(0, $userTableConfig['age']->options->get('min'));
        self::assertSame(65, $userTableConfig['age']->options->get('max'));

        self::assertInstanceOf(AnonymizerConfig::class, $userTableConfig['email']);
        self::assertSame('email', $userTableConfig['email']->anonymizer);
        self::assertSame('email', $userTableConfig['email']->targetName);
        self::assertSame('toto.com', $userTableConfig['email']->options->get('domain'));

        self::assertInstanceOf(AnonymizerConfig::class, $userTableConfig['address_0']);
        self::assertSame('address', $userTableConfig['address_0']->anonymizer);
        self::assertSame('address_0', $userTableConfig['address_0']->targetName);
        self::assertSame('street', $userTableConfig['address_0']->options->get('street_address'));
        self::assertNull($userTableConfig['address_0']->options->get('secondary_address'));
        self::assertSame('zip_code', $userTableConfig['address_0']->options->get('postal_code'));
        self::assertSame('city', $userTableConfig['address_0']->options->get('locality'));
        self::assertNull($userTableConfig['address_0']->options->get('region'));
        self::assertSame('country', $userTableConfig['address_0']->options->get('country'));
    }
}
