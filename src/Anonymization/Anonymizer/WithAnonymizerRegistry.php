<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer;

interface WithAnonymizerRegistry
{
    public function setAnonymizerRegistry(AnonymizerRegistry $anonymizerRegistry): void;
}
