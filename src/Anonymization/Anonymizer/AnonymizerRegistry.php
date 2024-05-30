<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer;

use Composer\InstalledVersions;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\AddressAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\ConstantAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\DateAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\EmailAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\FirstNameAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\FloatAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\IbanBicAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\IntegerAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\LastNameAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\LoremIpsumAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\Md5Anonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\NullAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\PasswordAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\StringAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

class AnonymizerRegistry
{
    // Core anonymizers list is hardcoded.
    private static $classNameList = [
        AddressAnonymizer::class,
        ConstantAnonymizer::class,
        DateAnonymizer::class,
        EmailAnonymizer::class,
        FirstNameAnonymizer::class,
        FloatAnonymizer::class,
        IbanBicAnonymizer::class,
        IntegerAnonymizer::class,
        LastNameAnonymizer::class,
        LoremIpsumAnonymizer::class,
        Md5Anonymizer::class,
        NullAnonymizer::class,
        PasswordAnonymizer::class,
        StringAnonymizer::class,
    ];

    private ?array $anonymizers = null;

    /**
     * @param null|string $projectDir
     *   Root directory that may contain a composer.json file (current project)
     *   and in which we will find the "vendor" directory.
     * @param null|string[] $paths
     *   List of path in which anonymizer PHP files will be looked up.
     *   Using this feature is discouraged, anonymizers should be registered
     *   using AnonymizerRegistry::register() instead for performance reasons.
     */
    public function __construct(
        private ?string $projectDir = null,
        private array $paths = [],
        private bool $legacyLookup = false,
    ) {
        if ($paths) {
            \trigger_deprecation('makinacorpus/db-tools-bundle', '2.0.0', 'Using \$paths arguments is deprecated, please use static registration instead.');
            $this->paths = \array_unique($paths);
        }
    }

    /**
     * Register a class name as being an anonymizer.
     *
     * Calling this after the registry has been initialized will be a no-op.
     */
    public static function register(array|string $className): void
    {
        foreach ((array) $className as $candidate) {
            try {
                $refClass = new \ReflectionClass($candidate);
                if (!$refClass->isSubclassOf(AbstractAnonymizer::class)) {
                    throw new \InvalidArgumentException(\sprintf("Class %s is not a subclass of %s.", $className, AbstractAnonymizer::class));
                }
                if (!$refClass->getAttributes()) {
                    throw new \InvalidArgumentException(\sprintf("Class %s must have the attribute %s.", $className, AsAnonymizer::class));
                }
                self::$classNameList[] = $candidate;
            } catch (\ReflectionException $e) {
                throw new \InvalidArgumentException(\sprintf("Class %s does not exist.", $className), 0, $e);
            }
        }
    }

    /**
     * Get all Anonymizers present in given paths.
     */
    public function getAnonymizers(): array
    {
        $this->initialize();

        return $this->anonymizers ?? [];
    }

    /**
     * Get anonymizer class name.
     */
    public function get(string $name): string
    {
        $this->initialize();

        return $this->getAnonymizers()[$name] ?? throw new \InvalidArgumentException(\sprintf("Can't find Anonymizer with name : %s, check your configuration.", $name));
    }

    /**
     * Lookup path and user provided names.
     */
    private function initialize(): void
    {
        if (null !== $this->anonymizers) {
            return;
        }

        $this->locatePacks();

        foreach (self::$classNameList as $className) {
            $this->processClassName($className, true);
        }

        if ($this->paths) {
            foreach ($this->paths as $path) {
                $this->requireAllPhpFilesIn($path);
            }
            foreach (\get_declared_classes() as $className) {
                $this->processClassName($className, false);
            }
        }
    }

    /**
     * Get anonymizer information from class.
     */
    private function processClassName(string $className, bool $errorIfInvalid = true): void
    {
        $refClass = new \ReflectionClass($className);

        if (!$refClass->isSubclassOf(AbstractAnonymizer::class)) {
            if ($errorIfInvalid) {
                throw new \InvalidArgumentException(\sprintf("Class %s is not a subclass of %s.", $className, AbstractAnonymizer::class));
            }
            return;
        }

        if (!$refClass->getAttributes(AsAnonymizer::class)) {
            if ($errorIfInvalid) {
                throw new \InvalidArgumentException(\sprintf("Class %s must have the attribute %s.", $className, AsAnonymizer::class));
            }
            return;
        }

        $this->anonymizers[$className::id()] = $className;
    }

    /**
     * Lookup given composer.json file and find paths in which we can lookup.
     */
    private function locatePacksInComposerFile(string $filename): void
    {
        try {
            $composerJson = @\json_decode(\file_get_contents($filename));
            if (!$composerJson && \json_last_error()) {
                \trigger_error(\sprintf("Unable to parse composer file: %s.", $filename), E_USER_ERROR);
                return; // @phpstan-ignore-line
            }
        } catch (\JsonException) {
            \trigger_error(\sprintf("Unable to parse composer file: %s.", $filename), E_USER_ERROR);
            return; // @phpstan-ignore-line
        }

        if (isset($composerJson->type) && $composerJson->type === 'db-tools-bundle-pack') {
            $packagePath = \dirname($filename);
            // @todo I'm not OK with hardcoding this.
            $anonymizersPath = $packagePath . '/src/Anonymizer/';

            if (\is_dir($anonymizersPath)) {
                if (!\in_array($anonymizersPath, $this->paths)) {
                    $this->paths[] = $anonymizersPath;
                }
            } else {
                // @todo Not sure this should raise exceptions, I think it would be
                //   better to simply add the whole source folder declared in autoload
                //   and live with it.
                throw new \DomainException(\sprintf(
                    "Pack of anonymizers '%s' (%s) as no 'src/Anonymizer/' directory and is thus not usable.",
                    $composerJson->name ?? 'no-name',
                    $packagePath
                ));
            }
        }
    }

    /**
     * Locate installed packs.
     */
    private function locatePacks(): void
    {
        if ($this->legacyLookup) {
            $this->locatePacksLegacy();
        }

        if (\class_exists(InstalledVersions::class)) {

            $ignoreList = $this->getVendorIgnoreList();

            foreach (InstalledVersions::getInstalledPackages() as $package) {
                if (\preg_match($ignoreList, $package)) {
                    continue;
                }

                $directory = InstalledVersions::getInstallPath($package);

                $this->locatePacksInComposerFile($directory . '/composer.json');
            }
        }
    }

    /**
     * Get vendor ignore list.
     */
    private function getVendorIgnoreList(): string
    {
        return '@(composer|doctrine|nikic|monolog|phar-io|phpunit|phpstan|psr|sebastian|symfony|twig)/*@';
    }

    /**
     * Find all php files in folder and require them.
     *
     * @deprecated
     *   Legacy lookup method will be removed in a future release.
     */
    private function requireAllPhpFilesIn(string $directory): void
    {
        if (!\is_dir($directory)) {
            throw new \LogicException(\sprintf("Given path '%s' is not a directory", $directory));
        }

        $iterator = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            ),
            '/^.+' . \preg_quote('.php') . '$/i',
            \RecursiveRegexIterator::GET_MATCH
        );

        foreach ($iterator as $file) {
            $sourceFile = $file[0];

            if (\preg_match('(^phar:)i', $sourceFile) === 0) {
                $sourceFile = \realpath($sourceFile);
            }

            require_once $sourceFile;
        }
    }

    /**
     * Locate installed packs (legacy method, no composer runtime available).
     *
     * @deprecated
     *   Legacy lookup method will be removed in a future release.
     */
    private function locatePacksLegacy(): void
    {
        \trigger_deprecation('makinacorpus/db-tools-bundle', '2.0.0', 'Using legacy lookup is deprecated.');

        if (null === $this->projectDir) {
            return;
        }

        $vendorDir = $this->projectDir . '/vendor';
        $ignoreList = $this->getVendorIgnoreList();

        // PHP cannot \glob('*/*/composer.json') directly, sad.
        foreach ($this->listDir($vendorDir, ['composer', 'bin']) as $vendor) {
            foreach ($this->listDir($vendorDir . '/' . $vendor) as $package) {
                $package = $vendor . '/' . $package;

                if (\preg_match($ignoreList, $package)) {
                    continue;
                }

                $this->locatePacksInComposerFile($vendorDir . '/' . $package . '/composer.json');
            }
        }
    }

    /**
     * @deprecated
     *   Legacy lookup method will be removed in a future release.
     */
    private function listDir(string $directory, array $ignore = []): array
    {
        if (false === ($handle = @\opendir($directory))) {
            \trigger_error(\sprintf("Could not open directory: %s.", $directory), E_USER_ERROR);

            return [];
        }

        $ignore[] = '.';
        $ignore[] = '..';

        $ret = [];

        while (false !== ($entry = \readdir($handle))) {
            if (!\in_array($entry, $ignore) && \is_dir($directory . '/' . $entry)) {
                $ret[] = $entry;
            }
        }

        return $ret;
    }
}
