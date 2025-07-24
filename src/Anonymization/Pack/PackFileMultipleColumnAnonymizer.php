<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Pack;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;

class PackFileMultipleColumnAnonymizer extends PackAnonymizer
{
    /**
     * @param array<string> $columns
     */
    public function __construct(
        string $id,
        ?string $description,
        Options $options,
        public readonly array $columns,
        public readonly string $filename,
    ) {
        parent::__construct($id, $description, $options);
    }
}
