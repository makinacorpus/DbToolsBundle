<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Loader;

use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationSingleConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use Symfony\Component\Yaml\Yaml;

class YamlLoader implements LoaderInterface
{
    public function __construct(
        private string $connectionName,
        private string $file,
    ) {}

    public function load(): AnonymizationConfig
    {
        $anonymizationConfig = new AnonymizationConfig();

        $yamlConfig = Yaml::parseFile($this->file);
        // var_dump($yamlConfig);
        if (!isset($yamlConfig[$this->connectionName])) {
            return $anonymizationConfig;
        }

        foreach ($yamlConfig[$this->connectionName] as $table => $tableConfigs) {
            foreach ($tableConfigs as $target => $config) {
                $config = \is_array($config) ? $config : ['anonymizer' => $config];

                $anonymizationConfig->add(new AnonymizationSingleConfig(
                    $table,
                    $target,
                    $config['anonymizer'] ?? throw new \InvalidArgumentException(\sprintf('Missing "anonymizer" for table "%s", key "%s"', $table, $target)),
                    new Options($config['options'] ?? []),
                ));
            }
        }
        $yamlConfig = $yamlConfig[$this->connectionName];

        return $anonymizationConfig;
    }
}
