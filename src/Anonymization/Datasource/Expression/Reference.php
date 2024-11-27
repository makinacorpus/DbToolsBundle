<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Expression;

use MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Context;
use MakinaCorpus\DbToolsBundle\Anonymization\Datasource\EnumDatasource;

class Reference extends Token
{
    public function __construct(
        string $datasource,
        int $expression,
        int $offset,
        public readonly string $referenced,
    ) {
        parent::__construct($datasource, $expression, $offset);
    }

    #[\Override]
    public function execute(Context $context): string
    {
        // @todo check for circular dependencies.
        try {
            $datasource = $context->getDatasource($this->referenced);

            if ($this->referenced === $this->datasource) {
                if (!$datasource instanceof EnumDatasource) {
                    $this->throwError("referenced datasource is not an enum");
                }
                if (!$datasource->count()) {
                    $this->throwError("referenced datasource is empty");
                }

                return $datasource->rawRandom();
            }

            return $datasource->random($context);

        } catch (\Throwable $e) {
            $this->throwError($e);
        }
    }
}
