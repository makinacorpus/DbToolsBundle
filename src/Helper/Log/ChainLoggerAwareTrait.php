<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper\Log;

use Psr\Log\LoggerInterface;

trait ChainLoggerAwareTrait
{
    private ?ChainLogger $chainLogger = null;

    /**
     * Add a logger to the internal chain logger.
     */
    public function addLogger(LoggerInterface $logger): static
    {
        $this->getChainLogger()->addLogger($logger);

        return $this;
    }

    /**
     * Know whether the internal chain logger contains the given logger.
     */
    public function hasLogger(LoggerInterface $logger): bool
    {
        return $this->getChainLogger()->hasLogger($logger);
    }

    /**
     * Get the chain logger. It will be initialized if necessary.
     */
    protected function getChainLogger(): ChainLogger
    {
        return $this->chainLogger ??= new ChainLogger();
    }
}
