<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Expression;

use MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Context;
use MakinaCorpus\DbToolsBundle\Error\DatasourceException;

abstract class Token
{
    public function __construct(
        public readonly string $datasource,
        public readonly int $expression,
        public readonly int $offset,
    ) {}

    public abstract function execute(Context $context): string;

    /**
     * Raise an error.
     */
    protected function throwError(string|\Throwable $error): never
    {
        $prefix = \sprintf('Datasource "%s" expression #%d at offset %d: ', $this->datasource, $this->expression, $this->offset);

        if ($error instanceof \Throwable) {
            throw new DatasourceException($prefix . $error->getMessage(), 0, $error);
        }
        throw new DatasourceException($prefix . $error);
    }
}
