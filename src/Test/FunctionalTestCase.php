<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Test;

use Doctrine\DBAL\Schema\Column;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Bridge\Symfony\DoctrineDatabaseSessionRegistry;
use MakinaCorpus\DbToolsBundle\Database\DatabaseSessionRegistry;
use MakinaCorpus\QueryBuilder\Bridge\Bridge;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineBridge;
use MakinaCorpus\QueryBuilder\BridgeFactory;
use MakinaCorpus\QueryBuilder\Dsn;
use MakinaCorpus\QueryBuilder\Error\Server\DatabaseObjectDoesNotExistError;

abstract class FunctionalTestCase extends UnitTestCase
{
    private ?Bridge $connection = null;
    private ?Bridge $privConnection = null;
    /** @var string[] */
    private array $createdTables = [];

    /** @after */
    protected function disconnect(): void
    {
        try {
            foreach ($this->createdTables as $tableName) {
                $this->dropTableIfExist($tableName);
            }
        } finally {
            $this->createdTables = [];
        }

        if (null !== $this->connection) {
            $this->connection->close();
            $this->connection = null;
        }

        if (null !== $this->privConnection) {
            $this->privConnection->close();
            $this->privConnection = null;
        }
    }

    /**
     * Initialize database.
     */
    protected function initializeDatabase(string $dbName): void
    {
        $privConnection = $this->getDatabaseSessionWithPrivileges();
        try {
            $privConnection->executeStatement("CREATE DATABASE ?::id", [$dbName]);
        } catch (\Throwable $e) {
            // Check database already exists or not.
            if (!\str_contains($e->getMessage(), 'exist')) {
                throw $e;
            }
        }
    }

    /**
     * Create query builder.
     */
    #[\Override]
    protected function getDatabaseSession(): Bridge
    {
        return $this->connection ??= $this->createBridge();
    }

    /**
     * Create priviledged query builder.
     */
    protected function getDatabaseSessionWithPrivileges(): Bridge
    {
        return $this->privConnection ??= $this->createPriviledgeBridge();
    }

    /**
     * Create database session registry.
     */
    protected function getDatabaseSessionRegistry(): DatabaseSessionRegistry
    {
        $bridge = $this->getDatabaseSession();

        if (!$bridge instanceof DoctrineBridge) {
            throw new \Exception("Connection is not a Doctrine bridge.");
        }

        $doctrineConnection = $bridge->getConnection();

        $doctrineRegistry = $this->createMock(ManagerRegistry::class);
        $doctrineRegistry
            ->method('getConnection')
            ->willReturn($doctrineConnection)
        ;
        $doctrineRegistry
            ->method('getConnections')
            ->willReturn([
                'default' => $doctrineConnection,
                // For backupper and restorer tests.
                'another' => $doctrineConnection,
            ])
        ;
        $doctrineRegistry
            ->method('getDefaultConnectionName')
            ->willReturn('default')
        ;

        return new DoctrineDatabaseSessionRegistry($doctrineRegistry);
    }

    /**
     * Skip for given database.
     */
    protected function skipIfDatabase(string $database, ?string $message = null): void
    {
        if ($this->getDatabaseSession()->vendorIs($database)) {
            self::markTestSkipped(\sprintf("Test disabled for database '%s'", $database));
        }
    }

    /**
     * Skip for given database.
     */
    protected function skipIfDatabaseNot(string $database, ?string $message = null): void
    {
        if (!$this->getDatabaseSession()->vendorIs($database)) {
            self::markTestSkipped(\sprintf("Test disabled for database '%s'", $database));
        }
    }

    /**
     * Skip for given database, and greater than version.
     */
    protected function skipIfDatabaseGreaterThan(string $database, string $version, ?string $message = null): void
    {
        $this->skipIfDatabase($database);

        if ($this->getDatabaseSession()->vendorVersionIs($version, '>=')) {
            self::markTestSkipped($message ?? \sprintf("Test disabled for database '%s' at version >= '%s'", $database, $version));
        }
    }

    /**
     * Skip for given database, and lower than version.
     */
    protected function skipIfDatabaseLessThan(string $database, string $version, ?string $message = null): void
    {
        if (!$this->getDatabaseSession()->vendorIs($database)) {
            return;
        }

        if ($this->getDatabaseSession()->vendorVersionIs($version, '<')) {
            self::markTestSkipped($message ?? \sprintf("Test disabled for database '%s' at version <= '%s'", $database, $version));
        }
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
        $this->dropTableIfExist($tableName);

        $tableBuilder = $this
            ->getDatabaseSession()
            ->getSchemaManager()
            ->modify()
            ->createTable($tableName)
        ;

        foreach ($columns as $name => $column) {
            if (\is_string($column)) {
                $tableBuilder->column(
                    name: $name,
                    type: $column,
                    nullable: true,
                );
            } elseif (\is_array($column)) {
                $tableBuilder->column(
                    name: $name,
                    type: $column['type'] ?? 'text',
                    nullable: !($column['notnull'] ?? true),
                );
            } elseif (!$column instanceof Column) {
                throw new \InvalidArgumentException(\sprintf("Column must be a string (type), and array (doctrine/dbal Column class options) or a %s instance", Column::class));
            }
        }

        $tableBuilder->endTable()->commit();

        $this->createdTables[] = $tableName;

        // We have to insert one by one because the test case might
        // use a different set of columns for each row.
        foreach ($rows as $row) {
            $this->getDatabaseSession()->insert($tableName)->values($row)->executeStatement();
        }
    }

    /**
     * Drop table if exists.
     */
    protected function dropTableIfExist(string $tableName): void
    {
        try {
            $this
                ->getDatabaseSession()
                ->getSchemaManager()
                ->modify()
                    ->dropTable($tableName)
                ->commit()
            ;
        } catch (DatabaseObjectDoesNotExistError) {
        }
    }

    /**
     * For anonymizers unit test, creates an anonymizator with the given
     * configuration, which should register all anonymizers required for
     * the test.
     */
    protected function createAnonymizatorWithConfig(AnonymizerConfig ...$anonymizerConfig): Anonymizator
    {
        $config = new AnonymizationConfig();

        foreach ($anonymizerConfig as $userConfig) {
            $config->add($userConfig);
        }

        return new Anonymizator(
            $this->getDatabaseSession(),
            new AnonymizerRegistry(),
            $config
        );
    }

    /**
     * Create connection.
     */
    private function createBridge(): Bridge
    {
        $params = $this->getConnectionParameters();

        if (!\str_contains($params['driver'], 'sqlite')) {
            if ($params['dbname']) {
                $this->initializeDatabase($params['dbname']);
            }
        }

        return BridgeFactory::createDoctrine($params);
    }

    /**
     * Create priviledged query builder.
     */
    private function createPriviledgeBridge(): Bridge
    {
        return BridgeFactory::createDoctrine($this->getPriviledgedConnectionParameters());
    }

    /**
     * Get connection parameters for user with privileges connection.
     *
     * This connection serves the purpose of initializing database.
     */
    private function getPriviledgedConnectionParameters(): array
    {
        if (!$dsnString = \getenv('DATABASE_URL')) {
            self::markTestSkipped("Missing 'DATABASE_URL' environment variable.");
        }
        $dsn = Dsn::fromString($dsnString);

        $driver = \getenv('DATABASE_DRIVER') ?: $dsn->getDriver();
        if ($driver === Dsn::DRIVER_ANY) {
            $driver = BridgeFactory::guessDoctrineDriver($dsn->getVendor(), $driver);
        }

        $driverOptions = [];
        if (\str_contains($driver, 'sqlsrv')) {
            // https://stackoverflow.com/questions/71688125/odbc-driver-18-for-sql-serverssl-provider-error1416f086
            $driverOptions['TrustServerCertificate'] = "true";
            $driverOptions['MultipleActiveResultSets'] = "false";
        }

        return \array_filter([
            'driver' => $driver,
            'host' => $dsn->getHost(),
            'password' => \getenv('DATABASE_ROOT_PASSWORD') ?: $dsn->getPassword(),
            'port' => $dsn->getPort(),
            'user' => \getenv('DATABASE_ROOT_USER') ?: $dsn->getUser(),
        ]) + $driverOptions;
    }

    /**
     * Get connection parameters for test user.
     */
    private function getConnectionParameters(): array
    {
        if (!$dsnString = \getenv('DATABASE_URL')) {
            self::markTestSkipped("Missing 'DATABASE_URL' environment variable.");
        }
        $dsn = Dsn::fromString($dsnString);

        $driver = \getenv('DATABASE_DRIVER') ?: $dsn->getDriver();
        if (Dsn::DRIVER_ANY === $driver) {
            $driver = BridgeFactory::guessDoctrineDriver($dsn->getVendor(), $driver);
        }

        $driverOptions = [];
        if (\str_contains($driver, 'sqlsrv')) {
            // https://stackoverflow.com/questions/71688125/odbc-driver-18-for-sql-serverssl-provider-error1416f086
            $driverOptions['TrustServerCertificate'] = "true";
            $driverOptions['MultipleActiveResultSets'] = "false";
        }

        return \array_filter([
            'dbname' => 'test_db',
            'driver' => $driver,
            'host' => $dsn->getHost(),
            'password' => $dsn->getPassword(),
            'port' => $dsn->getPort(),
            'user' => $dsn->getUser(),
        ] + $driverOptions);
    }
}
