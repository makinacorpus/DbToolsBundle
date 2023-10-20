<?php

declare(strict_types=1);

namespace Doctrine\Bundle\DoctrineBundle\CacheWarmer;

use MakinaCorpus\DbToolsBundle\Anonymizer\Anonymizator;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class AnonymizatorCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private Anonymizator $anonymizator
    ) {}

    /**
     * @inheritdoc
     */
    public function isOptional(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function warmUp($cacheDirectory): array
    {
        $this->anonymizator->loadConfiguration();

        return [];
    }
}
