<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Bridge\Laravel;

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Bridge\Laravel\DbToolsServiceProvider;
use MakinaCorpus\DbToolsBundle\Database\DatabaseSessionRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for DbToolsServiceProvider with a real Laravel application.
 *
 * Boots an actual Laravel app, creates an SQLite database, seeds data, 
 * and verifies that the DbToolsBundle anonymization works correctly end-to-end.
 *
 * @requires extension pdo_sqlite
 * @group laravel-integration
 */
class DbToolsServiceProviderTest extends TestCase
{
    private static string $dbPath;
    private static Application $app;

    public static function setUpBeforeClass(): void
    {
        self::$dbPath = \sys_get_temp_dir() . '/dbtoolsbundle_laravel_' . \uniqid() . '.sqlite';

        self::$app = self::bootLaravelApp(self::$dbPath);

        self::createSchema();
        self::seedData();
    }

    public static function tearDownAfterClass(): void
    {
        if (\file_exists(self::$dbPath)) {
            \unlink(self::$dbPath);
        }
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    /**
     * Verify that all expected DbToolsBundle services are registered in the
     * Laravel container after the ServiceProvider boots.
     */
    public function testServiceProviderRegistersAllServices(): void
    {
        $app = self::$app;

        $this->assertTrue(
            $app->bound(DatabaseSessionRegistry::class),
            'DatabaseSessionRegistry should be bound'
        );
        $this->assertTrue(
            $app->bound(\MakinaCorpus\DbToolsBundle\Configuration\ConfigurationRegistry::class),
            'ConfigurationRegistry should be bound'
        );
        $this->assertTrue(
            $app->bound(\MakinaCorpus\DbToolsBundle\Storage\Storage::class),
            'Storage should be bound'
        );
        $this->assertTrue(
            $app->bound(\MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry::class),
            'AnonymizerRegistry should be bound'
        );
        $this->assertTrue(
            $app->bound(\MakinaCorpus\DbToolsBundle\Backupper\BackupperFactory::class),
            'BackupperFactory should be bound'
        );
        $this->assertTrue(
            $app->bound(\MakinaCorpus\DbToolsBundle\Restorer\RestorerFactory::class),
            'RestorerFactory should be bound'
        );
    }

    /**
     * Verify the registry can resolve a database session for the SQLite connection.
     */
    public function testRegistryResolvesSessionForSqliteConnection(): void
    {
        /** @var DatabaseSessionRegistry $registry */
        $registry = self::$app->make(DatabaseSessionRegistry::class);

        $session = $registry->getDatabaseSession('sqlite');
        $this->assertNotNull($session);
        $this->assertInstanceOf(
            \MakinaCorpus\QueryBuilder\DatabaseSession::class,
            $session
        );
    }

    /**
     * Verify seeded data exists and looks correct before anonymization.
     */
    public function testDatabaseContainsSeededUsersAndPosts(): void
    {
        $pdo = new \PDO('sqlite:' . self::$dbPath);

        $users = $pdo->query('SELECT * FROM users ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(3, $users, 'Expected 3 seeded users');
        $this->assertSame('Alice Smith', $users[0]['name']);
        $this->assertSame('alice@example.com', $users[0]['email']);
        $this->assertSame('Bob Jones', $users[1]['name']);
        $this->assertSame('Charlie Brown', $users[2]['name']);

        $posts = $pdo->query('SELECT * FROM posts ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(3, $posts, 'Expected 3 seeded posts');
        $this->assertSame('Secret personal content about Alice', $posts[0]['body']);
    }

    /**
     * Verify that anonymization correctly mutates targeted columns.
     */
    public function testAnonymizesUserNameAndEmail(): void
    {
        /** @var DatabaseSessionRegistry $registry */
        $registry = self::$app->make(DatabaseSessionRegistry::class);
        $session = $registry->getDatabaseSession('sqlite');

        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig('users', 'name', 'string', new Options([
            'sample' => ['Anon User', 'Hidden Person', 'Redacted Name'],
        ])));
        $config->add(new AnonymizerConfig('users', 'email', 'email', new Options([])));

        $anonymizator = new Anonymizator($session, new AnonymizerRegistry(), $config);
        $anonymizator->addAnonymizerIdColumn('users');
        $anonymizator->anonymize();

        $pdo = new \PDO('sqlite:' . self::$dbPath);
        $rows = $pdo->query('SELECT name, email FROM users ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(3, $rows);

        foreach ($rows as $row) {
            // Names must have been replaced by something from the sample list
            $this->assertContains(
                $row['name'],
                ['Anon User', 'Hidden Person', 'Redacted Name'],
                'Name should be one of the sample values'
            );
            // Emails should still look like emails
            $this->assertStringContainsString('@', $row['email'], 'Anonymized email should contain @');
            // Emails must not be the originals
            $this->assertNotContains($row['email'], [
                'alice@example.com',
                'bob@example.com',
                'charlie@example.com',
            ], 'Original email should have been replaced');
        }
    }

    /**
     * Verify that posts.body is anonymized with lorem ipsum text.
     */
    public function testAnonymizesPostBody(): void
    {
        /** @var DatabaseSessionRegistry $registry */
        $registry = self::$app->make(DatabaseSessionRegistry::class);
        $session = $registry->getDatabaseSession('sqlite');

        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig('posts', 'body', 'lorem', new Options([])));

        $anonymizator = new Anonymizator($session, new AnonymizerRegistry(), $config);
        $anonymizator->addAnonymizerIdColumn('posts');
        $anonymizator->anonymize();

        $pdo = new \PDO('sqlite:' . self::$dbPath);
        $rows = $pdo->query('SELECT body FROM posts ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(3, $rows);
        foreach ($rows as $row) {
            $this->assertNotEmpty($row['body'], 'Body should not be empty after anonymization');
            $this->assertNotSame(
                'Secret personal content about Alice',
                $row['body'],
                'Body should have been anonymized'
            );
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Boot a minimal Laravel application with an in-memory config.
     */
    private static function bootLaravelApp(string $dbPath): Application
    {
        $basePath = \sys_get_temp_dir() . '/laravel_dbtoolstest_' . \uniqid();

        foreach ([
            '/storage/framework/sessions',
            '/storage/framework/views',
            '/storage/framework/cache',
            '/storage/logs',
            '/bootstrap/cache',
            '/config',
        ] as $dir) {
            \mkdir($basePath . $dir, 0755, true);
        }

        $app = new Application($basePath);

        $app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            \Illuminate\Foundation\Http\Kernel::class,
        );
        $app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \Illuminate\Foundation\Console\Kernel::class,
        );
        $app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \Illuminate\Foundation\Exceptions\Handler::class,
        );

        // Provide all config in-process — no files needed
        $app->singleton('config', function () use ($dbPath, $basePath) {
            return new \Illuminate\Config\Repository([
                'app' => [
                    'name'      => 'DbToolsTestApp',
                    'env'       => 'testing',
                    'key'       => 'base64:' . \base64_encode(\random_bytes(32)),
                    'providers' => [],
                ],
                'database' => [
                    'default'     => 'sqlite',
                    'connections' => [
                        'sqlite' => [
                            'driver'   => 'sqlite',
                            'database' => $dbPath,
                            'prefix'   => '',
                        ],
                    ],
                ],
                'logging' => [
                    'default'  => 'single',
                    'channels' => [
                        'single' => [
                            'driver' => 'single',
                            'path'   => $basePath . '/storage/logs/laravel.log',
                        ],
                    ],
                ],
                'db-tools' => [
                    'storage_directory'    => $basePath . '/storage/db_tools',
                    'anonymizer_paths'     => [],
                    'anonymization'        => [],
                    'anonymization_files'  => [],
                    'connections'          => [],
                ],
            ]);
        });

        $app->register(\Illuminate\Database\DatabaseServiceProvider::class);
        $app->register(\Illuminate\Log\LogServiceProvider::class);
        $app->register(DbToolsServiceProvider::class);

        Facade::setFacadeApplication($app);
        $app->boot();

        Container::setInstance($app);
        Application::setInstance($app);

        return $app;
    }

    /**
     * Create the users and posts tables — mirroring real Laravel Eloquent models.
     */
    private static function createSchema(): void
    {
        $pdo = new \PDO('sqlite:' . self::$dbPath);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $pdo->exec('PRAGMA foreign_keys = ON');

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS users (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                name       TEXT    NOT NULL,
                email      TEXT    NOT NULL UNIQUE,
                password   TEXT    NOT NULL,
                created_at DATETIME,
                updated_at DATETIME
            )
        SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS posts (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER NOT NULL,
                title      TEXT    NOT NULL,
                body       TEXT    NOT NULL,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        SQL);
    }

    /**
     * Seed realistic data.
     */
    private static function seedData(): void
    {
        $pdo = new \PDO('sqlite:' . self::$dbPath);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $now = \date('Y-m-d H:i:s');

        $pdo->exec(<<<SQL
            INSERT INTO users (name, email, password, created_at, updated_at) VALUES
                ('Alice Smith',   'alice@example.com',   'hashed_password_1', '$now', '$now'),
                ('Bob Jones',     'bob@example.com',     'hashed_password_2', '$now', '$now'),
                ('Charlie Brown', 'charlie@example.com', 'hashed_password_3', '$now', '$now')
        SQL);

        $pdo->exec(<<<SQL
            INSERT INTO posts (user_id, title, body, created_at, updated_at) VALUES
                (1, 'My First Post',  'Secret personal content about Alice', '$now', '$now'),
                (2, 'Bob''s Diary',   'Private notes from Bob Jones',        '$now', '$now'),
                (3, 'Charlie Speaks', 'Sensitive info about Charlie Brown',   '$now', '$now')
        SQL);
    }
}
