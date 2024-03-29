<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Test;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver\Middleware\EnableForeignKeys;
use Doctrine\DBAL\Driver\OCI8\Middleware\InitializeSession;
use Doctrine\DBAL\Exception\DatabaseObjectNotFoundException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Exception\TableDoesNotExist;
use Doctrine\DBAL\Types\Type;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use Doctrine\DBAL\Logging\Middleware;
use Psr\Log\AbstractLogger;

abstract class FunctionalTestCase extends UnitTestCase
{
    private array $createdTables = [];

    /**
     * Skip for given database.
     */
    protected function skipIfDatabase(string $database, ?string $message = null): void
    {
        if ($this->getDoctrineQueryBuilder()->getServerFlavor() === $database) {
            self::markTestSkipped(\sprintf("Test disabled for database '%s'", $database));
        }
    }

    /**
     * Skip for given database.
     */
    protected function skipIfDatabaseNot(string $database, ?string $message = null): void
    {
        if ($this->getDoctrineQueryBuilder()->getServerFlavor() !== $database) {
            self::markTestSkipped(\sprintf("Test disabled for database '%s'", $database));
        }
    }

    /**
     * Skip for given database, and greater than version.
     */
    protected function skipIfDatabaseGreaterThan(string $database, string $version, ?string $message = null): void
    {
        $this->skipIfDatabase($database);

        if ($this->getDoctrineQueryBuilder()->isVersionGreaterOrEqualThan($version)) {
            self::markTestSkipped($message ?? \sprintf("Test disabled for database '%s' at version >= '%s'", $database, $version));
        }
    }

    /**
     * Skip for given database, and lower than version.
     */
    protected function skipIfDatabaseLessThan(string $database, string $version, ?string $message = null): void
    {
        if ($this->getDoctrineQueryBuilder()->getServerFlavor() !== $database) {
            return;
        }

        if ($this->getDoctrineQueryBuilder()->isVersionLessThan($version)) {
            self::markTestSkipped($message ?? \sprintf("Test disabled for database '%s' at version <= '%s'", $database, $version));
        }
    }

    /**
     * Get real query builder.
     */
    protected function getDoctrineQueryBuilder(): DoctrineQueryBuilder
    {
        return new DoctrineQueryBuilder($this->getConnection());
    }

    /**
     * Create table with columns.
     *
     * @param array<string,string|array|Column> $columns
     * @param array<array<string,mixed>> $rows
     */
    protected function createOrReplaceTable(
        string $tableName,
        array $columns,
        array $rows = [],
    ): void {
        $defaultColumnOptions = [
            'notnull' => false,
        ];

        foreach ($columns as $name => $column) {
            if (\is_string($column)) {
                $columns[$name] = new Column($name, Type::getType($column), $defaultColumnOptions);
            } elseif (\is_array($column)) {
                if (isset($column['type'])) {
                    $type = Type::getType($column['type']);
                    unset($column['type']);
                } else {
                    $type = Type::getType('string');
                }
                $columns[$name] = new Column($name, $type, $column + $defaultColumnOptions);
            } elseif (!$column instanceof Column) {
                throw new \InvalidArgumentException(\sprintf("Column must be a string (type), and array (doctrine/dbal Column class options) or a %s instance", Column::class));
            }
        }

        $this->dropTableIfExist($tableName);

        $connection = $this->getConnection();
        $connection->createSchemaManager()->createTable(new Table($tableName, $columns));

        $this->createdTables[] = $tableName;

        // We have to insert one by one because the test case might
        // use a different set of columns for each row.
        $queryBuilder = $this->getDoctrineQueryBuilder();
        foreach ($rows as $row) {
            $queryBuilder->insert($tableName)->values($row)->executeStatement();
        }
    }

    /**
     * Drop table if exists.
     */
    protected function dropTableIfExist(string $tableName): void
    {
        try {
            $this
                ->getConnection()
                ->createSchemaManager()
                ->dropTable($tableName)
            ;
        } catch (TableDoesNotExist|TableNotFoundException|DatabaseObjectNotFoundException) {
        }
    }

    /** @after */
    #[\Override]
    protected function disconnect(): void
    {
        parent::disconnect();

        try {
            foreach ($this->createdTables as $tableName) {
                $this->dropTableIfExist($tableName);
            }
        } finally {
            $this->createdTables = [];
        }
    }

    #[\Override]
    protected function createConnection(): Connection
    {
        $params = $this->getConnectionParameters();

        return DriverManager::getConnection(
            $params,
            self::createConfiguration($params['driver']),
        );
    }

    #[\Override]
    protected function initializeDatabase(): void
    {
        $privConnection = $this->createPrivConnection();
        try {
            $privConnection->createSchemaManager()->createDatabase('test_db');
        } catch(\Exception $e) {

        } finally {
            $privConnection->close();
        }
    }

    /**
     * Get connection parameters from environment.
     */
    private function getConnectionParameters(): array
    {
        if (!$driver = \getenv('DBAL_DRIVER')) {
            self::markTestSkipped("Missing 'DBAL_DRIVER' environment variable.");
        }

        $driverOptions = [];
        if (\str_contains($driver, 'sqlsrv')) {
            // https://stackoverflow.com/questions/71688125/odbc-driver-18-for-sql-serverssl-provider-error1416f086
            $driverOptions['TrustServerCertificate'] = "true";
        }

        $params = \array_filter([
            'dbname' => \getenv('DBAL_DBNAME'),
            'driver' => $driver,
            'driverOptions' => $driverOptions,
            'host' => \getenv('DBAL_HOST'),
            'password' => \getenv('DBAL_PASSWORD'),
            'port' => \getenv('DBAL_PORT'),
            'user' => \getenv('DBAL_USER'),
            'path' => \getenv('DBAL_PATH'),
        ]);

        return $params;
    }

    /**
     * Connexion with administration rights, for database setup.
     */
    private function createPrivConnection(): Connection
    {
        $params = $this->getConnectionParameters();

        if ($value = \getenv('DBAL_ROOT_USER')) {
            $params['user'] = $value;
        }
        if ($value = \getenv('DBAL_ROOT_PASSWORD')) {
            $params['password'] = $value;
        }
        // Avoid error upon connection when database does not exit.
        unset($params['dbname']);

        return DriverManager::getConnection(
            $params,
            self::createConfiguration($params['driver']),
        );
    }

    /**
     * Code copied and adapted from doctrine/dbal package.
     *
     * @see \Doctrine\DBAL\Tests\FunctionalTestCase
     */
    private static function createConfiguration(string $driver): Configuration
    {
        $configuration = new Configuration();
        $middlewares = [];

        // @todo Option
        /* @phpstan-ignore-next-line */
        if (false) {
            $middlewares[] = new Middleware(
                new class () extends AbstractLogger {
                    #[\Override]
                    public function log($level, string|\Stringable $message, array $context = []): void
                    {
                        if (\str_contains($message, 'Executing statement')) {
                            echo $message, \print_r($context, true), "\n";
                        }
                    }
                },
            );
        }

        switch ($driver) {
            case 'pdo_oci':
            case 'oci8':
                $middlewares[] = new InitializeSession();
                break;
            case 'pdo_sqlite':
            case 'sqlite3':
                $middlewares[] = new EnableForeignKeys();
                break;
        }

        $configuration->setMiddlewares($middlewares);

        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        return $configuration;
    }
}
