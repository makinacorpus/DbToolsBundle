<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Pack;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;

/**
 * Generates a value using a generation pattern.
 *
 * Multiple generation patterns can be given, each new generated sample will
 * randomly use of them.
 */
class PackEnumGeneratedAnonymizer extends PackAnonymizer
{
    /**
     * @param array<\MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Pattern\StringPattern|null> $pattern
     */
    public function __construct(
        string $id,
        ?string $description,
        Options $options,
        public readonly array $pattern,
    ) {
        parent::__construct($id, $description, $options);
    }
}
