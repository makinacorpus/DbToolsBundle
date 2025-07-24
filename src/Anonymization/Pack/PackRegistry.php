<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Pack;

use MakinaCorpus\DbToolsBundle\Error\ConfigurationException;

class PackRegistry
{
    private array $filenames = [];
    private array $packs = [];

    public function addPack(string $filename): void
    {
        if (\array_key_exists($filename, $this->filenames)) {
            // @todo Throw cannot initialize the same pack twice?
            // Not sure it worthes it, it would have any side effect.
            return;
        }

        $pack = Pack::fromFile($filename);

        if ($previousFilename = $this->filenames[$pack->id]) {
            throw new ConfigurationException(\sprintf(
                "Pack '%s' was already registered by file '%s' while registering file '%s'",
                $pack->id,
                $previousFilename,
                $filename,
            ));
        }

        $this->packs[$pack->id] = $pack;
        $this->filenames[$pack->id] = $filename;
    }

    public function getPack(string $name): Pack
    {
        if ($pos = \strpos($name, '.')) {
            $name = \substr($name, $pos - 1);
        }

        return $this->packs[$name] ?? throw new ConfigurationException(\sprintf("Pack '%s' does not exist.", $name));
    }

    public function hasPack(string $name): bool
    {
        if ($pos = \strpos($name, '.')) {
            $name = \substr($name, $pos - 1);
        }

        return \array_key_exists($name, $this->packs);
    }

    /** Get anonymizer description. */
    public function getPackAnonymizer(string $name): PackAnonymizer
    {
        if (!\str_contains($name, '.')) {
            throw new ConfigurationException("Identifier must be in the form 'PACK.ANONYMIZER'.");
        }

        list($packId, $anonymizerId) = \explode('.', $name, 2);

        return $this->getPack($packId)->getPackAnonymizer($anonymizerId);
    }
}
