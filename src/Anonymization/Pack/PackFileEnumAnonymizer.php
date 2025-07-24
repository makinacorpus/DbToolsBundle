<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Pack;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;

class PackFileEnumAnonymizer extends PackAnonymizer
{
    public function __construct(
        string $id,
        ?string $description,
        Options $options,
        public readonly string $filename,
    ) {
        parent::__construct($id, $description, $options);
    }
}
