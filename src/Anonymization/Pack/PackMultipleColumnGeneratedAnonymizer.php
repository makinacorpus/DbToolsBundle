<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Pack;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Error\ConfigurationException;

/**
 * Generates column values using generation patterns.
 */
class PackMultipleColumnGeneratedAnonymizer extends PackAnonymizer
{
    /**
     * @param array<string> $columns
     * @param array<\MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Pattern\StringPattern|null> $patterns
     *   Count must match $columns count.
     */
    public function __construct(
        string $id,
        ?string $description,
        Options $options,
        public readonly array $columns,
        public readonly array $patterns,
    ) {
        parent::__construct($id, $description, $options);

        if (\count($columns) !== \count($patterns)) {
            throw new ConfigurationException("Column count and pattern count must match.");
        }
    }
}
