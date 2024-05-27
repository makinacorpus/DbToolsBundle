<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Test;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver\Middleware\EnableForeignKeys;
use Doctrine\DBAL\Driver\OCI8\Middleware\InitializeSession;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Bridge\Symfony\DoctrineDatabaseSessionRegistry;
use MakinaCorpus\DbToolsBundle\Database\DatabaseSessionRegistry;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Error\Server\DatabaseObjectDoesNotExistError;
use Psr\Log\AbstractLogger;

abstract class FunctionalTestCase extends UnitTestCase
{
    private bool $initialized = false;
    /**
     * @deprecated
     *   Remove when hard dependency on doctrine/dbal will be gone.
     */
    private ?Connection $connection = null;
    /**
     * @var string[]
     */
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

        if ($this->connection) {
            while ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            unset($this->connection);
        }
    }

    /**
     * Initialize database.
     */
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
     * Get testing connection object.
     *
     * @deprecated
     *   Remove when hard dependency on doctrine/dbal will is gone.
     */
    protected function getDoctrineConnection(): Connection
    {
        if (!$this->initialized) {
            $this->initializeDatabase();

            $this->initialized = true;
        }

        return $this->connection ??= $this->createDoctrineConnection();
    }

    /**
     * Get database session.
     */
    #[\Override]
    protected function getDatabaseSession(): DatabaseSession
    {
        return new DoctrineQueryBuilder($this->getDoctrineConnection());
    }

    /**
     * Create database session registry.
     */
    protected function getDatabaseSessionRegistry(): DatabaseSessionRegistry
    {
        $doctrineConnection = $this->getDoctrineConnection();

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
     * Create database connection.
     *
     * Default will use a mock object.
     *
     * @deprecated
     *   Remove when hard dependency on doctrine/dbal will be gone.
     */
    private function createDoctrineConnection(): Connection
    {
        $params = $this->getConnectionParameters();

        return DriverManager::getConnection(
            $params,
            self::createConfiguration($params['driver']),
        );
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
            'port' => ($port = \getenv('DBAL_PORT')) ? ((int) $port) : null,
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
