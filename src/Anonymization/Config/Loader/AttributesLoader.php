<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Config\Loader;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

class AttributesLoader implements LoaderInterface
{
    public function __construct(
        private EntityManagerProvider $entityManagerProvider,
    ) {}

    #[\Override]
    public function load(AnonymizationConfig $config): void
    {
        try {
            $entityManager = $this->entityManagerProvider->getManager($config->connectionName);
        } catch (\InvalidArgumentException) {
            // Entity manager on the given connection does not exists.
            // Simply pass attribute lookup.
            return;
        }

        $metadataFactory = $entityManager->getMetadataFactory();
        $metadatas = $metadataFactory->getAllMetadata();

        foreach ($metadatas as $metadata) {
            \assert($metadata instanceof ClassMetadata);
            if ($metadata->isMappedSuperclass || $metadata->isEmbeddedClass) {
                continue;
            }

            $embeddedClassesConfig = [];
            foreach($metadata->embeddedClasses as $name => $embeddedClass) {
                $className = $embeddedClass['class'];
                $embeddedClassesConfig[$className] = [];
                $reflexionClass = new \ReflectionClass($className);
                foreach ($reflexionClass->getProperties() as $reflexionProperty) {
                    if ($attributes = $reflexionProperty->getAttributes(Anonymize::class)) {
                        $anonymization = $attributes[0]->newInstance();
                        $embeddedClassesConfig[$className][$reflexionProperty->getName()] = $anonymization;
                    }
                }
            }

            $reflexionClass = $metadata->getReflectionClass();
            if ($attributes = $reflexionClass->getAttributes(Anonymize::class)) {
                // There can only be one of those attributes, foreach() is
                // required because of reflection API signature.
                foreach ($attributes as $key => $attribute) {
                    $anonymization = $attribute->newInstance();
                    $config->add(new AnonymizerConfig(
                        $metadata->getTableName(),
                        // For a anonymization setted on table, we give an arbitrary name.
                        $anonymization->type . '_' . $key,
                        $anonymization->type,
                        new Options($anonymization->options),
                    ));
                }
            }

            foreach ($metadata->fieldMappings as $fieldName => $fieldValues) {
                // Field name with dot are part of Embeddables.
                if (\str_contains($fieldName, '.')) {
                    if (\key_exists($fieldValues['originalClass'], $embeddedClassesConfig)) {
                        $embeddedClassConfig = $embeddedClassesConfig[$fieldValues['originalClass']];
                        if (\key_exists($fieldValues['originalField'], $embeddedClassConfig)) {
                            $propertyConfig = $embeddedClassConfig[$fieldValues['originalField']];
                            $config->add(new AnonymizerConfig(
                                $metadata->getTableName(),
                                $metadata->getColumnName($fieldName),
                                $propertyConfig->type,
                                new Options($propertyConfig->options),
                            ));
                        }
                    }
                    continue;
                }

                $columnName = $metadata->getColumnName($fieldName);
                if ($metadata->isInheritedField($fieldName)) {
                    $fieldMapping = $metadata->getFieldMapping($fieldName);
                    // @phpstan-ignore-next-line
                    if (\is_array($fieldMapping)) {
                        // Code for doctrine/orm:^2.0.
                        $ownerClass = $fieldMapping['inherited'];
                    } else {
                        // Code for doctrine/orm:^3.0.
                        $ownerClass = $fieldMapping->inherited;
                    }
                    $parentMetadata = $metadataFactory->getMetadataFor($ownerClass);
                    \assert($parentMetadata instanceof ClassMetadata);
                    $tableName = $parentMetadata->getTableName();
                } else {
                    $tableName = $metadata->getTableName();
                }

                if ($attributes = $metadata->getReflectionProperty($fieldName)->getAttributes(Anonymize::class)) {
                    $anonymization = $attributes[0]->newInstance();
                    $config->add(new AnonymizerConfig(
                        $tableName,
                        $columnName,
                        $anonymization->type,
                        new Options($anonymization->options),
                    ));
                }
            }
        }
    }
}
