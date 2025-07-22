<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer;

trait WithAnonymizerRegistryTrait /* implements WithAnonymizerRegistry */
{
    private AnonymizerRegistry $anonymizerRegistry;

    #[\Override]
    public function setAnonymizerRegistry(AnonymizerRegistry $anonymizerRegistry): void
    {
        $this->anonymizerRegistry = $anonymizerRegistry;
    }

    protected function getAnonymizerRegistry(): AnonymizerRegistry
    {
        return $this->anonymizerRegistry ?? throw new \LogicException(\sprintf("Did you forget to call %s::getAnonymizerRegistry()", static::class));
    }
}
