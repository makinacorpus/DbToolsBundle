<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Expression;

use MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Context;

class Range extends Token
{
    public function __construct(
        string $datasource,
        int $expression,
        int $offset,
        public readonly int $min,
        public readonly int $max,
    ) {
        parent::__construct($datasource, $expression, $offset);
    }

    #[\Override]
    public function execute(Context $context): string
    {
        return (string) \rand($this->min, $this->max);
    }
}
