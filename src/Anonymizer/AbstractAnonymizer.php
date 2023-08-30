<?php

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target\Target;

abstract class AbstractAnonymizer
{
    public function __construct(
        protected Connection $connection,
    ) { }

    /**
     * Initialize your anonymizer
     *
     * Override this method for your needs, for example create a temporary
     * table with dummy data.
     *
     * This method is launch once at the beginning of the anonymization process.
     */
    public function initialize(): self
    {
        return $this;
    }

    /**
     * Add statement to existing update query to anonymize a specific target.
     */
    abstract public function anonymize(QueryBuilder $updateQuery, Target $target, Options $options): self;

    /**
     * Clean your anonymizer
     *
     * Override this method for your needs, for example drop a temporary
     * table created in initialiaze() methode.
     *
     * This method is only launch once at the end of the anonymization process.
     */
    public function clean(): self
    {
        return $this;
    }

    protected function generateTempTableName(): string
    {
        return \uniqid('anonymizer_sample_');
    }

    protected function getSqlRandomExpression(): string
    {
        $plateform = $this->connection->getDatabasePlatform();

        return match (true) {
            $plateform instanceof MySQLPlatform => "rand()",
            $plateform instanceof PostgreSQLPlatform => "random()",
            default => throw new \InvalidArgumentException(\sprintf('%s is not supported.', \get_class($plateform)))
        };
    }
}