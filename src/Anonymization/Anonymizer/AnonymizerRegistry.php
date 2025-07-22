<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer;

use Composer\InstalledVersions;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
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

    /** @var array<string, string> */
    private ?array $classes = null;
    /** @var array<string, AsAnonymizer> */
    private ?array $metadata = null;
    private array $paths = [];

    public function __construct(?array $paths = null)
    {
        $this->addPath($paths ?? []);
    }

    /**
     * Add path in which to lookup for anonymizers.
     */
    public function addPath(array $paths): void
    {
        $this->paths = \array_unique(\array_merge($this->paths, $paths));
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
        Options $options,
        DatabaseSession $databaseSession,
    ): AbstractAnonymizer {
        $className = $this->getAnonymizerClass($name);

        $ret = new $className($config->table, $config->targetName, $databaseSession, $options);
        \assert($ret instanceof AbstractAnonymizer);

        if ($ret instanceof WithAnonymizerRegistry) {
            $ret->setAnonymizerRegistry($this);
        }

        return $ret;
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
                } else {
                    \trigger_error(\sprintf("Anonymizers pack '%s' in '%s' as no 'src/Anonymizer/' directory and is thus not usable.", $package, $directory), \E_USER_ERROR);
                }
            }
        }
    }

    private function throwAnonymizerDoesNotExist(string $name): never
    {
        throw new \InvalidArgumentException(\sprintf("Can't find Anonymizer with name : %s, check your configuration.", $name));
    }
}
