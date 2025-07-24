<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Pack;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;

class PackMultipleColumnAnonymizer extends PackAnonymizer
{
    /**
     * @param array<string> $columns
     * @param iterable<array<string>> $data
     */
    public function __construct(
        string $id,
        ?string $description,
        Options $options,
        public readonly array $columns,
        public readonly iterable $data,
    ) {
        parent::__construct($id, $description, $options);
    }
}
