<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Loader;

use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

class YamlLoader implements LoaderInterface
{
    public function __construct(
        private string $file,
        private string $connectionName = 'default',
    ) {}

    public function load(AnonymizationConfig $config): void
    {
        $yamlConfig = Yaml::parseFile($this->file);

        if ($this->connectionName !== $config->connectionName) {
            return;
        }

        $resolver = (new OptionsResolver())
            ->setRequired('anonymizer')
            ->setAllowedTypes('anonymizer', 'string')
            ->setDefault('options', [])
            ->setAllowedTypes('options', 'array')
        ;

        foreach ($yamlConfig as $table => $tableConfigs) {
            foreach ($tableConfigs as $target => $targetConfig) {
                try {
                    $targetConfig = \is_array($targetConfig) ? $targetConfig : ['anonymizer' => $targetConfig];
                    $targetConfig = $resolver->resolve($targetConfig);
                } catch (ExceptionInterface $e) {
                    $message = $e->getMessage();
                    throw new \InvalidArgumentException(
                        <<<TXT
                        Error while validating configuration for table '${table}', key '${target}':
                        ${message}
                        TXT
                    );
                }

                $config->add(new AnonymizerConfig(
                    $table,
                    $target,
                    $targetConfig['anonymizer'],
                    new Options($targetConfig['options']),
                ));
            }
        }
    }
}
