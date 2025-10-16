<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer;

use Composer\InstalledVersions;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Pack\PackAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Pack\PackEnumAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Pack\PackEnumGeneratedAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Pack\PackFileEnumAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Pack\PackFileMultipleColumnAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Pack\PackMultipleColumnAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Pack\PackMultipleColumnGeneratedAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Pack\PackRegistry;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\DatabaseSession;

class AnonymizerRegistry
{
    // Core anonymizers list is hardcoded.
    private static $coreAnonymizers = [
        Core\AddressAnonymizer::class,
        Core\ConstantAnonymizer::class,
        Core\DateAnonymizer::class,
        Core\EmailAnonymizer::class,
        Core\FileEnumAnonymizer::class,
        Core\FileMultipleColumnAnonymizer::class,
        Core\FirstNameAnonymizer::class,
        Core\FloatAnonymizer::class,
        Core\IbanBicAnonymizer::class,
        Core\IntegerAnonymizer::class,
        Core\LastNameAnonymizer::class,
        Core\LoremIpsumAnonymizer::class,
        Core\Md5Anonymizer::class,
        Core\NullAnonymizer::class,
        Core\PasswordAnonymizer::class,
        Core\StringAnonymizer::class,
        Core\StringPatternAnonymizer::class,
    ];

    private PackRegistry $packRegistry;

    /** @var array<string, string> */
    private ?array $classes = null;

    /** @var array<string, AsAnonymizer> */
    private ?array $metadata = null;

    /**
     * Paths where to lookup for custom anonymizers.
     *
     * @var array<string>
     */
    private array $paths = [];

    /**
     * Pack filenames where to lookup for PHP-less packs.
     *
     * @var array<string>
     */
    private array $packs = [];

    public function __construct(?array $paths = null, ?array $packs = null)
    {
        $this->addPath($paths ?? []);
        $this->addPack($packs ?? []);
        $this->packRegistry = new PackRegistry();
    }

    /**
     * Add path in which to lookup for anonymizers.
     */
    public function addPath(array $paths): void
    {
        $this->paths = \array_unique(\array_merge($this->paths, $paths));
    }

    /**
     * Add PHP-less configuration file pack.
     */
    public function addPack(array $packs): void
    {
        $this->packs = \array_unique(\array_merge($this->packs, $packs));
    }

    /**
     * Get all registered anonymizers classe names.
     *
     * @return array<string,AsAnonymizer>
     */
    public function getAllAnonymizerMetadata(): array
    {
        $this->initialize();

        return $this->metadata;
    }

    /**
     * Create anonymizer instance.
     */
    public function createAnonymizer(
        string $name,
        AnonymizerConfig $config,
        Context $context,
        DatabaseSession $databaseSession,
    ): AbstractAnonymizer {
        if ($this->packRegistry->hasPack($name)) {
            $ret = $this->createAnonymizerFromPack(
                $this->packRegistry->getPackAnonymizer($name),
                $config,
                $context,
                $databaseSession,
            );
        } else {
            $className = $this->getAnonymizerClass($name);

            $ret = new $className($config->table, $config->targetName, $databaseSession, $context, $config->options);
            \assert($ret instanceof AbstractAnonymizer);
        }

        if ($ret instanceof WithAnonymizerRegistry) {
            $ret->setAnonymizerRegistry($this);
        }

        return $ret;
    }

    /**
     * Create anonymizer instance from pack.
     */
    private function createAnonymizerFromPack(
        PackAnonymizer $packAnonymizer,
        AnonymizerConfig $config,
        Context $context,
        DatabaseSession $databaseSession
    ): AbstractAnonymizer {
        // Merge incomming user options with options from the pack.
        // Pack given options will override the user one.
        $options = $config->options->with($packAnonymizer->options->all());

        // Anonymizer from pack factory. Hardcoded for now.
        if ($packAnonymizer instanceof PackEnumAnonymizer) {
            return new Core\StringAnonymizer(
                $config->table,
                $config->targetName,
                $databaseSession,
                $context,
                // @todo Convert data to an array if an iterable was
                //   here. Later, change getSample() signature of
                //   AbstractEnumAnonymizer to accept any iterable.
                $options->with([
                    'sample' => \is_array($packAnonymizer->data) ? $packAnonymizer->data : \iterator_to_array($packAnonymizer->data),
                ]),
            );
        }

        if ($packAnonymizer instanceof PackMultipleColumnAnonymizer) {
            return new Core\MultipleColumnAnonymizer(
                $config->table,
                $config->targetName,
                $databaseSession,
                // @todo Convert data to an array if an iterable was
                //   here. Later, change getSample() signature of
                //   AbstractEnumAnonymizer to accept any iterable.
                $options->with([
                    'columns' => $packAnonymizer->columns,
                    'sample' => \is_array($packAnonymizer->data) ? $packAnonymizer->data : \iterator_to_array($packAnonymizer->data),
                ]),
            );
        }

        if ($packAnonymizer instanceof PackEnumGeneratedAnonymizer) {
            if (1 !== \count($packAnonymizer->pattern)) {
                // @todo
                throw new \LogicException("Not implemented yet: pattern anonymizer does not support multiple patterns yet.");
            }

            return new Core\StringPatternAnonymizer(
                $config->table,
                $config->targetName,
                $databaseSession,
                $context,
                $options->with([
                    'pattern' => $packAnonymizer->pattern[0],
                ]),
            );
        }

        if ($packAnonymizer instanceof PackMultipleColumnGeneratedAnonymizer) {
            // @todo
            throw new \LogicException("Not implemented yet: missing arbitrary column generator anonymizer.");
        }

        if ($packAnonymizer instanceof PackFileEnumAnonymizer) {
            return new Core\FileEnumAnonymizer(
                $config->table,
                $config->targetName,
                $databaseSession,
                $context,
                $options->with(['source' => $packAnonymizer->filename]),
            );
        }

        if ($packAnonymizer instanceof PackFileMultipleColumnAnonymizer) {
            return new Core\FileMultipleColumnAnonymizer(
                $config->table,
                $config->targetName,
                $databaseSession,
                $context,
                $options->with([
                    'columns' => $packAnonymizer->columns,
                    'source' => $packAnonymizer->filename,
                ]),
            );
        }

        throw new \LogicException(\sprintf("Pack anonymizer with class '%s' is not implement yet.", \get_class($packAnonymizer)));
    }

    /**
     * Get anonymizer metadata.
     */
    public function getAnonymizerMetadata(string $name): AsAnonymizer
    {
        $this->initialize();

        return $this->metadata[$name] ?? $this->throwAnonymizerDoesNotExist($name);
    }

    /**
     * @internal
     *   For unit tests only, please do not use.
     */
    public function getAnonymizerClass(string $name): string
    {
        $this->initialize();

        return $this->classes[$name] ?? $this->throwAnonymizerDoesNotExist($name);
    }

    private function getAnonymizatorClassMetadata(string $className): AsAnonymizer
    {
        if ($attributes = (new \ReflectionClass($className))->getAttributes(AsAnonymizer::class)) {
            return $attributes[0]->newInstance();
        }

        throw new \LogicException(\sprintf("Class '%s' should have an '%s' attribute.", $className, AsAnonymizer::class));
    }

    /**
     * Lazy initialization.
     */
    private function initialize(): void
    {
        if (null !== $this->classes) {
            return;
        }

        $this->classes = [];
        $this->metadata = [];

        foreach (self::$coreAnonymizers as $className) {
            $this->addAnonymizer($className, true);
        }

        $this->locatePacks();

        if ($this->paths) {
            $found = false;

            foreach ($this->paths as $path) {
                if (!\is_dir($path)) {
                    throw new \LogicException(\sprintf("Given path '%s' is not a directory.", $path));
                }

                // Find all PHP files in the given directory.
                $iterator = new \RegexIterator(
                    new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator(
                            $path,
                            \FilesystemIterator::SKIP_DOTS
                        ),
                        \RecursiveIteratorIterator::LEAVES_ONLY
                    ),
                    '/^.+' . \preg_quote('.php') . '$/i',
                    \RecursiveRegexIterator::GET_MATCH,
                );

                foreach ($iterator as $file) {
                    $found = true;

                    $sourceFile = $file[0];
                    if (\preg_match('(^phar:)i', $sourceFile) === 0) {
                        $sourceFile = \realpath($sourceFile);
                    }

                    // Require file for its content to be present in the
                    // \get_declared_classes() function result.
                    require_once $sourceFile;
                }
            }

            if ($found) {
                foreach (\get_declared_classes() as $className) {
                    $this->addAnonymizer($className, false);
                }
            }
        }

        if ($this->packs) {
            foreach ($this->packs as $filename) {
                $this->packRegistry->addPack($filename);
            }
        }
    }

    /**
     * Add anonymizer definition.
     */
    private function addAnonymizer(string $className, bool $failOnError = false): void
    {
        $refClass = new \ReflectionClass($className);

        // Ignore nbn-concrete usable classes.
        if ($refClass->isAbstract()) {
            if ($failOnError) {
                throw new \InvalidArgumentException(\sprintf("'%s': class is abstract.", $className));
            }
            return;
        }
        if (!$refClass->isSubclassOf(AbstractAnonymizer::class)) {
            if ($failOnError) {
                throw new \InvalidArgumentException(\sprintf("'%s': class does not extend '%s'.", $className, AbstractAnonymizer::class));
            }
            return;
        }

        $metadata = $this->getAnonymizatorClassMetadata($className);
        $id = $metadata->id();

        $this->classes[$id] = $className;
        $this->metadata[$id] = $metadata;
    }

    /**
     * Locate installed packs.
     */
    private function locatePacks(): void
    {
        if (\class_exists(InstalledVersions::class)) {
            foreach (InstalledVersions::getInstalledPackagesByType('db-tools-bundle-pack') as $package) {
                $directory = InstalledVersions::getInstallPath($package);
                $path = $directory . '/src/Anonymizer/';
                if (\is_dir($path)) {
                    $this->addPath([$path]);
                } elseif (\file_exists($path . '/db_tools.pack.yaml')) {
                    $this->addPack([$path . '/db_tools.pack.yaml']);
                } else {
                    \trigger_error(\sprintf("Anonymizers pack '%s' in '%s' as no 'src/Anonymizer/' directory nor 'db_tools.pack.yaml' file and is thus not usable.", $package, $directory), \E_USER_ERROR);
                }
            }
        }
    }

    private function throwAnonymizerDoesNotExist(string $name): never
    {
        throw new \InvalidArgumentException(\sprintf("Can't find Anonymizer with name : %s, check your configuration.", $name));
    }
}
