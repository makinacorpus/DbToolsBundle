<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Bridge\Standalone;

/**
 * Allow user to plug into the database session creation.
 *
 * This is intented for edge cases, such as multi-tenant architectures for
 * example where you would like to be able to change the database URL using
 * environment variables for example.
 *
 * As of now, this is intended to use in standalone flavor only, in most
 * cases when integrating with a framework such as Symfony or Laravel, the
 * framework will fully handle the database connections by itself.
 *
 * @experimental
 */
interface ConnectionProvider
{
    /**
     * Dynamically create the database DSN, such as:
     *   "pgsql://username:password@hostname:port/database?version=16.0"
     *
     * If you handle a single connection using your implementation, you may
     * skip entirely using the $name parameter and simply return the DSN.
     *
     * If you manage more than one connection and $name is erroneous or
     * unknown, you are free to raise any exception, this will force the
     * process to stop.
     */
    public function createConnectionDsn(string $name): string;
}
