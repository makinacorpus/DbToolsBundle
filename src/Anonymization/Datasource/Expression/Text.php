<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Expression;

use MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Context;

class Text extends Token
{
    public function __construct(
        string $datasource,
        int $expression,
        int $offset,
        public readonly string $text,
    ) {
        parent::__construct($datasource, $expression, $offset);
    }

    #[\Override]
    public function execute(Context $context): string
    {
        return $this->text;
    }
}
