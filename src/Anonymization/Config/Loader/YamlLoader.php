<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Config\Loader;

use Symfony\Component\Yaml\Yaml;

class YamlLoader extends ArrayLoader
{
    public function __construct(
        private string $file,
        string $connectionName = 'default',
    ) {
        parent::__construct([], $connectionName);
    }

    #[\Override]
    protected function getData(): array
    {
        return Yaml::parseFile($this->file);
    }
}
