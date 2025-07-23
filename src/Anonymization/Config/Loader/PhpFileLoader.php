<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Config\Loader;

use MakinaCorpus\DbToolsBundle\Error\ConfigurationException;

class PhpFileLoader extends ArrayLoader
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
        try {
            $data = require $this->file;
        } catch (\Throwable $e) {
            throw new ConfigurationException(\sprintf(
                "An error occurred when loading \"%s\" configuration file: %s",
                $this->file,
                $e->getMessage()
            ), 0, $e);
        }

        if (!\is_array($data)) {
            throw new ConfigurationException(\sprintf(
                "File \"%s\" is not a valid PHP anonymization configuration file (must return an array).",
                $this->file
            ));
        }

        return $data;
    }
}
