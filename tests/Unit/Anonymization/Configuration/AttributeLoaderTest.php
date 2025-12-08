<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Configuration;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\Loader\AttributesLoader;
use MakinaCorpus\DbToolsBundle\Test\UnitTestCase;
use MakinaCorpus\DbToolsBundle\Tests\Resources\Loader\TestEntity;
use MakinaCorpus\DbToolsBundle\Tests\Resources\Loader\TestEntityWithEmbedded;
use MakinaCorpus\DbToolsBundle\Tests\Resources\Loader\TestJoinedChild;
use MakinaCorpus\DbToolsBundle\Tests\Resources\Loader\TestJoinedParent;

class AttributeLoaderTest extends UnitTestCase
{
    public function testLoadOk(): void
    {
        $classMetadataFactory = $this->getClassMetadataFactory();
        $classMetadataFactory->getMetadataFor(TestEntity::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->exactly(1))
            ->method('getMetadataFactory')
            ->willReturn($classMetadataFactory)
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

    public function testLoadWithJoinedInheritance(): void
    {
        $classMetadataFactory = $this->getClassMetadataFactory();
        $classMetadataFactory->getMetadataFor(TestJoinedChild::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->exactly(1))
            ->method('getMetadataFactory')
            ->willReturn($classMetadataFactory)
        ;

        $entityManagerProvider = $this->createMock(EntityManagerProvider::class);
        $entityManagerProvider
            ->expects($this->exactly(1))
            ->method('getManager')
            ->willReturn($entityManager)
        ;

        // We load configuration for the 'default' connection.
        $config = new AnonymizationConfig('default');
        (new AttributesLoader($entityManagerProvider))->load($config);

        // Then we validate what's in it:
        $testTableConfig = $config->getTableConfig('test_joined_child');
        self::assertCount(2, $testTableConfig);
        self::assertInstanceOf(AnonymizerConfig::class, $testTableConfig['url']);
        self::assertSame('constant', $testTableConfig['url']->anonymizer);
        self::assertInstanceOf(AnonymizerConfig::class, $testTableConfig['thumbnail_url']);
        self::assertSame('constant', $testTableConfig['thumbnail_url']->anonymizer);

        $testTableConfig = $config->getTableConfig('test_joined_parent');
        self::assertCount(1, $testTableConfig);
        self::assertInstanceOf(AnonymizerConfig::class, $testTableConfig['email']);
        self::assertSame('email', $testTableConfig['email']->anonymizer);
    }

    public function testLoadWithEmbeddedOk(): void
    {
        $classMetadataFactory = $this->getClassMetadataFactory();
        $classMetadataFactory->getMetadataFor(TestEntityWithEmbedded::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->exactly(1))
            ->method('getMetadataFactory')
            ->willReturn($classMetadataFactory)
        ;

        $entityManagerProvider = $this->createMock(EntityManagerProvider::class);
        $entityManagerProvider
            ->expects($this->exactly(1))
            ->method('getManager')
            ->willReturn($entityManager)
        ;

        // We load configuration for the 'default' connection.
        $config = new AnonymizationConfig('default');
        (new AttributesLoader($entityManagerProvider))->load($config);

        // Then we validate what's in it:
        $testTableConfig = $config->getTableConfig('test_with_embedded');
        self::assertCount(3, $testTableConfig);

        self::assertInstanceOf(AnonymizerConfig::class, $testTableConfig['email']);
        self::assertSame('email', $testTableConfig['email']->anonymizer);
        self::assertSame('email', $testTableConfig['email']->targetName);
        self::assertSame('toto.com', $testTableConfig['email']->options->get('domain'));

        self::assertInstanceOf(AnonymizerConfig::class, $testTableConfig['embeddableEntity_age']);
        self::assertSame('integer', $testTableConfig['embeddableEntity_age']->anonymizer);
        self::assertSame('embeddableEntity_age', $testTableConfig['embeddableEntity_age']->targetName);
        self::assertSame(0, $testTableConfig['embeddableEntity_age']->options->get('min'));
        self::assertSame(65, $testTableConfig['embeddableEntity_age']->options->get('max'));

        self::assertInstanceOf(AnonymizerConfig::class, $testTableConfig['embeddableEntity_size']);
        self::assertSame('integer', $testTableConfig['embeddableEntity_size']->anonymizer);
        self::assertSame('embeddableEntity_size', $testTableConfig['embeddableEntity_size']->targetName);
        self::assertSame(60, $testTableConfig['embeddableEntity_size']->options->get('min'));
        self::assertSame(250, $testTableConfig['embeddableEntity_size']->options->get('max'));
    }

    private function getClassMetadataFactory(): ClassMetadataFactory
    {
        $attributeDriver = new AttributeDriver([
            \dirname(\dirname(\dirname(__DIR__))) . '/Resources/Loader'
        ]);

        $configuration = new \Doctrine\ORM\Configuration();
        $configuration->setMetadataDriverImpl($attributeDriver);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('getConfiguration')
            ->willReturn($configuration)
        ;
        $entityManager
            ->method('getEventManager')
            ->willReturn(new EventManager())
        ;

        $classMetadataFactory = new ClassMetadataFactory();
        $classMetadataFactory->setEntityManager($entityManager);

        return $classMetadataFactory;
    }
}
