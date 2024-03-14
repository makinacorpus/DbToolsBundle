<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Config\Loader;

use InvalidArgumentException;
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

    public function load(AnonymizationConfig $config): void
    {
        try {
            $entityManager = $this->entityManagerProvider->getManager($config->connectionName);
        } catch (InvalidArgumentException) {
            // Entity manager on the given connection does not exists.
            // Simply pass attribute lookup.
            return;
        }

        $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();

        foreach ($metadatas as $metadata) {
            \assert($metadata instanceof ClassMetadata);
            if ($metadata->isMappedSuperclass) {
                continue;
            }

            $reflexionClass = $metadata->getReflectionClass();
            if ($attributes = $reflexionClass->getAttributes(Anonymize::class)) {
                foreach ($attributes as $key => $attribute) {
                    $anonymization = $attribute->newInstance();
                    $config->add(new AnonymizerConfig(
                        $metadata->getTableName(),
                        // For a anonymization setted on table, we give an arbitrary name
                        $anonymization->type . '_' . $key,
                        $anonymization->type,
                        new Options($anonymization->options),
                    ));
                }
            }

            foreach ($metadata->getFieldNames() as $fieldName) {
                // Field name with dot are part of Embeddables
                // @see
                //  - https://www.doctrine-project.org/projects/doctrine-orm/en/3.0/tutorials/embeddables.html#separating-concerns-using-embeddables
                //  - https://github.com/makinacorpus/DbToolsBundle/issues/105
                if (\str_contains($fieldName, '.')) {
                    continue;
                }

                $reflexionProperty = $reflexionClass->getProperty($fieldName);
                if ($attributes = $reflexionProperty->getAttributes(Anonymize::class)) {
                    $anonymization = $attributes[0]->newInstance();
                    $config->add(new AnonymizerConfig(
                        $metadata->getTableName(),
                        $metadata->getColumnName($fieldName),
                        $anonymization->type,
                        new Options($anonymization->options),
                    ));
                }
            }
        }
    }
}
