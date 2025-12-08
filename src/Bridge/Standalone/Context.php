<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Bridge\Standalone;

use MakinaCorpus\DbToolsBundle\Anonymization\AnonymizatorFactory;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Backupper\BackupperFactory;
use MakinaCorpus\DbToolsBundle\Database\DatabaseSessionRegistry;
use MakinaCorpus\DbToolsBundle\Restorer\RestorerFactory;
use MakinaCorpus\DbToolsBundle\Stats\StatsProviderFactory;
use MakinaCorpus\DbToolsBundle\Storage\Storage;
use Psr\Log\LoggerInterface;

/**
 * Standalone API context, with all components.
 */
class Context
{
    public function __construct(
        public readonly AnonymizatorFactory $anonymizatorFactory,
        public readonly AnonymizerRegistry $anonymizerRegistry,
        public readonly BackupperFactory $backupperFactory,
        public readonly DatabaseSessionRegistry $databaseSessionRegistry,
        public readonly LoggerInterface $logger,
        public readonly RestorerFactory $restorerFactory,
        public readonly StatsProviderFactory $statsProviderFactory,
        public readonly Storage $storage,
    ) {}
}
