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
    ) {}

    public function load(string $connectionName): AnonymizationConfig
    {
        $anonymizationConfig = new AnonymizationConfig();

        $yamlConfig = Yaml::parseFile($this->file);

        if (!isset($yamlConfig[$connectionName])) {
            return $anonymizationConfig;
        }

        $resolver = (new OptionsResolver())
            ->setRequired('anonymizer')
            ->setAllowedTypes('anonymizer', 'string')
            ->setDefault('options', [])
            ->setAllowedTypes('options', 'array')
        ;

        foreach ($yamlConfig[$connectionName] as $table => $tableConfigs) {
            foreach ($tableConfigs as $target => $config) {
                try {
                    $config = \is_array($config) ? $config : ['anonymizer' => $config];
                    $config = $resolver->resolve($config);
                } catch (ExceptionInterface $e) {
                    $message = $e->getMessage();
                    throw new \InvalidArgumentException(<<<TXT
                    Error while validating configuration for table '${table}', key '${target}':
                    ${message}
                    TXT);
                }

                $anonymizationConfig->add(new AnonymizerConfig(
                    $table,
                    $target,
                    $config['anonymizer'],
                    new Options($config['options']),
                ));
            }
        }

        return $anonymizationConfig;
    }
}
