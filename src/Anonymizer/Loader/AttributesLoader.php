<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Loader;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

class AttributesLoader implements LoaderInterface
{
    public function __construct(
        private EntityManagerProvider $entityManagerProvider,
    ) {}

    public function load(string $connectionName): AnonymizationConfig
    {
        $config = new AnonymizationConfig();

        $entityManager = $this->entityManagerProvider->getManager($connectionName);

        $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();

        foreach ($metadatas as $metadata) {
            \assert($metadata instanceof ClassMetadata);
            $reflexionClass = $metadata->getReflectionClass();
            if ($attributes = $reflexionClass->getAttributes(Anonymize::class)) {
                foreach ($attributes as $key => $attribute) {
                    $anonymization = $attribute->newInstance();
                    $config->add(new AnonymizerConfig(
                        $metadata->getTableName(),
                        // For a anonymization setted on table, we give an arbitrary name
                        $anonymization->anonymizer . '_' . $key,
                        $anonymization->anonymizer,
                        new Options($anonymization->options),
                    ));
                }
            }

            foreach ($metadata->getFieldNames() as $fieldName) {
                $reflexionProperty = $reflexionClass->getProperty($fieldName);
                if ($attributes = $reflexionProperty->getAttributes(Anonymize::class)) {
                    $anonymization = $attributes[0]->newInstance();
                    $config->add(new AnonymizerConfig(
                        $metadata->getTableName(),
                        $metadata->getColumnName($fieldName),
                        $anonymization->anonymizer,
                        new Options($anonymization->options),
                    ));
                }
            }
        }

        return $config;
    }
}
