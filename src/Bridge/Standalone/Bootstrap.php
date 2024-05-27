<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Bridge\Standalone;

use Composer\InstalledVersions;
use MakinaCorpus\DbToolsBundle\Anonymization\AnonymizatorFactory;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Backupper\BackupperFactory;
use MakinaCorpus\DbToolsBundle\Command\Anonymization\AnonymizeCommand;
use MakinaCorpus\DbToolsBundle\Command\Anonymization\AnonymizerListCommand;
use MakinaCorpus\DbToolsBundle\Command\Anonymization\CleanCommand;
use MakinaCorpus\DbToolsBundle\Command\Anonymization\ConfigDumpCommand;
use MakinaCorpus\DbToolsBundle\Command\BackupCommand;
use MakinaCorpus\DbToolsBundle\Command\CheckCommand;
use MakinaCorpus\DbToolsBundle\Command\RestoreCommand;
use MakinaCorpus\DbToolsBundle\Command\StatsCommand;
use MakinaCorpus\DbToolsBundle\Database\DatabaseSessionRegistry;
use MakinaCorpus\DbToolsBundle\Database\StandaloneDatabaseSessionRegistry;
use MakinaCorpus\DbToolsBundle\Error\ConfigurationException;
use MakinaCorpus\DbToolsBundle\Restorer\RestorerFactory;
use MakinaCorpus\DbToolsBundle\Stats\StatsProviderFactory;
use MakinaCorpus\DbToolsBundle\Storage\Storage;
use MakinaCorpus\QueryBuilder\Error\ConfigurationError;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Yaml\Yaml;

/**
 * Creates Symfony console application.
 *
 * @internal
 * @see ../../bin/db-tools.php
 */
class Bootstrap
{
    /**
     * Create and run Symfony console application.
     */
    public static function run(): void
    {
        static::createApplication()->run();
    }

    /**
     * Create Symfony console application.
     */
    public static function createApplication(): Application
    {
        // @todo Test in PHAR context.
        if (\class_exists(InstalledVersions::class)) {
            $version = InstalledVersions::getVersion('makinacorpus/db-tools-bundle');
        }
        $version ??= 'cli';
        \assert($version !== null);

        $application = new Application('Db Tools', $version);
        $application->setCatchExceptions(true);
        $application->setDefaultCommand('list');

        $definition = $application->getDefinition();
        $definition->addOption(new InputOption('config', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Configuration files', null));
        $definition->addOption(new InputOption('env', null, InputOption::VALUE_REQUIRED, 'Environment', 'dev'));

        // Hack, we need it output to have the same configuration as the application.
        $input = new ArgvInput();
        $output = new ConsoleOutput();
        (\Closure::bind(fn () => $application->configureIO($input, $output), null, Application::class))();

        // We need to parse a few arguments prior running the console
        // application in order to setup commands. This is hackish but
        // should work.
        $config = $configFiles = [];
        if ($input->hasOption('config')) {
            foreach ((array) $input->getOption('config') as $filename) {
                $configFiles[] = $filename;
            }
        }

        $commands = [
            'anonymization:clean' => [
                fn (Context $context) => new CleanCommand(
                    anonymizatorFactory: $context->anonymizatorFactory,
                    defaultConnectionName: $context->databaseSessionRegistry->getDefaultConnectionName(),
                ),
                'Clean DbTools left-over temporary tables',
                ['clean'],
            ],
            'anonymization:config-dump' => [
                fn (Context $context) => new ConfigDumpCommand(
                    anonymizatorFactory: $context->anonymizatorFactory,
                ),
                'Dump anonymization configuration',
                ['config-dump'],
            ],
            'anonymization:list' => [
                fn (Context $context) => new AnonymizerListCommand(
                    anonymizerRegistry: $context->anonymizerRegistry,
                ),
                'List all available anonymizers',
                [],
            ],
            'anonymization:run' => [
                fn (Context $context) => new AnonymizeCommand(
                    defaultConnectionName: $context->databaseSessionRegistry->getDefaultConnectionName(),
                    restorerFactory: $context->restorerFactory,
                    backupperFactory: $context->backupperFactory,
                    anonymizatorFactory: $context->anonymizatorFactory,
                    storage: $context->storage,
                ),
                'Anonymize given backup file or the local database',
                ['anonymize'],
            ],
            'database:backup' => [
                fn (Context $context) => new BackupCommand(
                    defaultConnectionName: $context->databaseSessionRegistry->getDefaultConnectionName(),
                    backupperFactory: $context->backupperFactory,
                    storage: $context->storage,
                ),
                'Backup database',
                ['backup'],
            ],
            'database:check' => [
                fn (Context $context) => new CheckCommand(
                    defaultConnectionName: $context->databaseSessionRegistry->getDefaultConnectionName(),
                    backupperFactory: $context->backupperFactory,
                    restorerFactory: $context->restorerFactory,
                ),
                'Check backup and restore binaries',
                ['check'],
            ],
            'database:restore' => [
                fn (Context $context) => new RestoreCommand(
                    defaultConnectionName: $context->databaseSessionRegistry->getDefaultConnectionName(),
                    restorerFactory: $context->restorerFactory,
                    storage: $context->storage,
                ),
                'Restore database.',
                ['restore'],
            ],
            'database:stats' => [
                fn (Context $context) => new StatsCommand(
                    defaultConnectionName: $context->databaseSessionRegistry->getDefaultConnectionName(),
                    statsProviderFactory: $context->statsProviderFactory,
                ),
                'Give some database statistics',
                ['stats'],
            ],
        ];

        $initializer = static fn (): Context => self::bootstrap($config, $configFiles, new ConsoleLogger($output));

        // All commands are wrapped into LazyCommand instances, we do not
        // really care about performances here, we have really few commands
        // and it's OK to initialiaze them all, but we need to change their
        // name to shorten them.
        foreach ($commands as $name => $data) {
            list($callback, $description, $aliases) = $data;
            $application->add(
                new LazyCommand(
                    name: $name,
                    aliases: $aliases,
                    description: $description,
                    isHidden: false,
                    commandFactory: fn () => $callback($initializer()),
                ),
            );
        }

        return $application;
    }

    /**
     * Bootstrap components as a standalone application.
     *
     * @param array<string,mixed> $config
     *   Configuration parsed from application bootstrap using CLI options.
     *   This configuration must match the Symfony configuration file without
     *   the "db_tools" root level.
     * @param array<string> $configFiles
     *   Additional configuration files to parse.
     */
    public static function bootstrap(array $config = [], array $configFiles = [], ?LoggerInterface $logger = null): Context
    {
        $logger ?? new NullLogger();
        $config = self::configParse($config, $configFiles, $logger);

        $databaseSessionRegistry = self::createDatabaseSessionRegistry($config);

        $anonymizerRegistry = self::createAnonymizeRegistry($config);
        $anonymizatorFactory = new AnonymizatorFactory($databaseSessionRegistry, $anonymizerRegistry, $logger);

        $backupperBinaries = $config['backupper_binaries'];
        $backupperExcludedTables = $config['excluded_tables'] ?? [];
        $backupperOptions = $config['backupper_options'];
        $backupperFactory = new BackupperFactory($databaseSessionRegistry, $backupperBinaries, $backupperOptions, $backupperExcludedTables, $logger);

        $restorerBinaries = $config['restorer_binaries'];
        $restorerOptions = $config['restorer_options'];
        $restorerFactory = new RestorerFactory($databaseSessionRegistry, $restorerBinaries, $restorerOptions, $logger);

        $statsProviderFactory = new StatsProviderFactory($databaseSessionRegistry);
        $storage = self::createStorage($config, $logger);

        return new Context(
            anonymizatorFactory: $anonymizatorFactory,
            anonymizerRegistry: $anonymizerRegistry,
            backupperFactory: $backupperFactory,
            databaseSessionRegistry: $databaseSessionRegistry,
            logger: $logger,
            restorerFactory: $restorerFactory,
            statsProviderFactory: $statsProviderFactory,
            storage: $storage,
        );
    }

    /**
     * Gets the application root dir (path of the project's composer file).
     */
    private static function getProjectDir(LoggerInterface $logger): ?string
    {
        // 4 level of \dirname() gets us in this project parent folder.
        $candidates = [\getcwd(), \dirname(__DIR__, 4)];

        foreach ($candidates as $candidate) {
            $dir = $candidate;
            while ($dir) {
                if (\is_file($dir.'/composer.json')) {
                    $logger->notice('Project root directory found: {dir}', ['dir' => $dir]);

                    return $dir;
                }
                $logger->debug('Not found project directory: {dir}', ['dir' => $dir]);
                $dir = \dirname($dir);
            }
        }
        return null;
    }

    /**
     * Parse configuration files, and environment provided configuration.
     *
     * @param array $config
     *   Overriding configuration from user input. It will overide configuration
     *   from given files.
     * @param array $files
     *   Configuration files, in override order in case of conflict.
     *
     * @return array
     *   Merged proper configuration.
     */
    private static function configParse(array $config, array $files, LoggerInterface $logger): array
    {
        $configFileNames = ['db_tools.yaml', 'db_tools.yml', 'db_tools.config.yaml', 'db_tools.config.yml'];
        $projectRoot = self::getProjectDir($logger);
        $workdir = $config['workdir'] ?? $projectRoot ?? \getcwd();

        // When no configuration file given, attempt to find one.
        // @todo Should stop at first when found.
        if (empty($files)) {
            $candidates = [];
            if ($projectRoot) {
                foreach ($configFileNames as $filename) {
                    $candidates[] = self::pathConcat($projectRoot, $filename);
                }
            }
            if ($projectRoot !== $workdir) {
                foreach ($configFileNames as $filename) {
                    $candidates[] = self::pathConcat($workdir, $filename);
                }
            }
            // Will not work under Windows (and that's OK).
            if ($homedir = \getenv("HOME")) {
                // @todo .config folder is configurable with XDG portals?
                $candidates[] = self::pathConcat($homedir, '/.config/db_tools/config.yaml');
                $candidates[] = self::pathConcat($homedir, '/.config/db_tools/config.yml');
                // As dot files.
                foreach ($configFileNames as $filename) {
                    $candidates[] = self::pathConcat($homedir, '.' . $filename);
                }
            }

            foreach ($candidates as $filename) {
                if (\file_exists($filename)) {
                    if (\is_readable($filename)) {
                        $logger->notice("Found configuration file: {file}", ['file' => $filename]);
                        $files[] = $filename;
                    } else {
                        $logger->warning("Configuration file could not be read: {file}", ['file' => $filename]);
                    }
                } else {
                    $logger->debug("Configuration file does not exist: {file}", ['file' => $filename]);
                }
            }
        }

        $configs = [];
        foreach ($files as $filename) {
            $configs[] = self::configParseFile($filename);
        }
        $configs[] = $config;
        $configs[] = self::configGetEnv();

        // Use symfony/config and our bundle configuration, which allows us
        // to use it fully for validation and merge.
        $configuration = new StandaloneConfiguration();
        $processor = new Processor();

        $config = $processor->processConfiguration($configuration, $configs);

        // Set a base directory for file and backup lookup.
        $config['workdir'] ?? $workdir;

        return $config;
    }

    /**
     * Parse a single configuration file.
     */
    private static function configParseFile(string $filename): array
    {
        if (!\file_exists($filename)) {
            throw new ConfigurationException(\sprintf("%s: file does not exist.", $filename));
        }
        if (!\is_readable($filename)) {
            throw new ConfigurationException(\sprintf("%s: file cannot be read.", $filename));
        }

        // 0 is not a good index for extension, this fails for false and 0.
        if (!($pos = \strrpos($filename, '.'))) {
            throw new ConfigurationException(\sprintf("%s: file extension cannot be guessed.", $filename));
        }
        $ext = \substr($filename, $pos + 1);

        $config = match ($ext) {
            'json' => \json_decode(\file_get_contents($filename), true, 512, \JSON_THROW_ON_ERROR),
            'yml', 'yaml' => Yaml::parseFile($filename),
            default => throw new ConfigurationException(\sprintf("%s: file extension '%s' is unsupported.", $filename, $ext)),
        };

        // Resolve all known filenames relative to this file.
        // @todo Warning, this code will only work on UNIX-like filesystems.
        $workdir = \rtrim($config['workdir'] ?? \dirname($filename), '/');

        // Storage root directory.
        if ($path = ($config['storage']['root_dir'] ?? null)) {
            $config['storage']['root_dir'] = self::pathAbs($workdir, $path);
        }

        // YAML anonymizer file paths.
        $yaml = $config['anonymization']['yaml'] ?? null;
        if (isset($yaml)) {
            if (\is_array($yaml)) {
                foreach ($yaml as $name => $path) {
                    $config['anonymization']['yaml'][$name] = self::pathAbs($workdir, $path);
                }
            } else {
                $config['anonymization']['yaml'] = self::pathAbs($workdir, $yaml);
            }
        }

        // Custom anonymizer paths.
        foreach (($config['anonymizer_paths'] ?? []) as $index => $path) {
            $config['anonymizer_paths'][$index] = self::pathAbs($workdir, $path);
        }

        return $config;
    }

    /**
     * Get config variables from environment variables.
     */
    private static function configGetEnv(): array
    {
        $config = [];

        // @todo read env variables, validate each, override $config

        return $config;
    }

    /**
     * Create anonymizer registry and register custom code and additional packs.
     */
    private static function createAnonymizeRegistry(array $config): AnonymizerRegistry
    {
        $projectDir = null;
        $paths = [];

        // @todo find a way to register packs when not in a composer project

        return new AnonymizerRegistry($projectDir, $paths);
    }

    /**
     * Create database session registry from config-given connections.
     */
    private static function createDatabaseSessionRegistry(array $config): DatabaseSessionRegistry
    {
        if (empty($config['connections'])) {
            throw new ConfigurationError("No database connection found, this means that either you forgot it into your configuration file, or no configuration files were found. Please run using the -vvv switch for more information.");
        }

        return new StandaloneDatabaseSessionRegistry($config['connections'], $config['default_connection']);
    }

    /**
     * Create storage.
     */
    private static function createStorage(array $config, LoggerInterface $logger): Storage
    {
        $rootdir = $config['storage']['root_dir'] ?? $config['workdir'];

        if (!\is_dir($rootdir)) {
            if (\file_exists($rootdir)) {
                throw new ConfigurationException(\sprintf("Storage root folder is a regular file instead of a directory: %s", $rootdir));
            }

            $logger->notice("Storage root folder does not exists: {dir}", ['dir' => $rootdir]);
        } else {
            $logger->notice("Found storage root folder: {dir}", ['dir' => $rootdir]);
        }

        return new Storage($config['storage']['root_dir'], $config['backup_expiration_age']);
    }

    /**
     * Concat and make absolute using given root if relative.
     */
    private static function pathAbs(string $root, string ...$pieces): string
    {
        $path = self::pathConcat(...$pieces);
        if (\str_starts_with($path, '/')) {
            return $path;
        }
        return self::pathConcat($root, $path);
    }

    /**
     * Concat all path segment while cleaning a bit given input.
     */
    private static function pathConcat(string ...$pieces): string
    {
        $first = true;
        foreach ($pieces as $index => $piece) {
            if ($first) {
                $first = false;
                $pieces[$index] = \rtrim($piece, '/\\');
            } else {
                if (\str_starts_with($piece, './')) {
                    $piece = \substr($piece, 2);
                }
                $pieces[$index] = \trim($piece, '/\\');
            }
        }
        return \implode(DIRECTORY_SEPARATOR, $pieces);
    }
}
