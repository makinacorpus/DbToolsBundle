<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class ChainLogger extends AbstractLogger
{
    /** @var LoggerInterface[] */
    private array $loggers = [];

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = []): void
    {
        foreach ($this->loggers as $logger) {
            $logger->log($level, $message, $context);
        }
    }

    public function addLogger(LoggerInterface $logger): void
    {
        $this->loggers[] = $logger;
    }

    public function hasLogger(LoggerInterface $logger): bool
    {
        return \in_array($logger, $this->loggers, true);
    }
}
