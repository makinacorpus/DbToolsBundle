<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Config\Loader;

use Symfony\Component\Yaml\Yaml;

class YamlLoader extends ArrayLoader
{
    public function __construct(
        private string $file,
        string $connectionName = 'default',
        /**
         * Root directory from which the configuration was loaded. It allows
         * later file loading (for example, when sources are CSV or TXT files).
         *
         * If not set, the file directory will be used instead. If set, it will
         * override the file directory.
         */
        ?string $basePath = null,
    ) {
        // @todo dirname() is purely soft logic, it does not imply any
        //   syscalls, whereas realpah() may.
        $basePath ??= \realpath(\dirname($file));

        parent::__construct([], $connectionName, $basePath);
    }

    #[\Override]
    protected function getData(): array
    {
        return Yaml::parseFile($this->file);
    }
}
