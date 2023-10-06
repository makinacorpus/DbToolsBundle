<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

class AnonymizerRegistry
{
    private ?array $anonymizers = null;

    public function __construct(
        private array $paths
    ) {}

    public function addPath(array $paths): void
    {
        $this->paths = \array_unique(\array_merge($this->paths, $paths));
    }

    /**
     * Get all Anonymizers present in given paths
     */
    public function getAnonymizers(): array
    {
        if ($this->anonymizers !== null) {
            return $this->anonymizers;
        }

        if (!$this->paths) {
            return [];
        }

        $classes = [];
        $includedFiles = [];

        foreach ($this->paths as $path) {
            if (! \is_dir($path)) {
                throw new \LogicException(\sprintf("Given path '%s' is not a directory", $path));
            }

            $iterator = new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
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

                $includedFiles[] = $sourceFile;
            }
        }

        $declared = \get_declared_classes();

        foreach ($declared as $className) {
            if ((new \ReflectionClass($className))->getAttributes(AsAnonymizer::class)) {
                if (!\is_subclass_of($className, AbstractAnonymizer::class)) {
                    throw new \InvalidArgumentException(\sprintf(
                        '"%s" should extends "%s".',
                        $className,
                        AbstractAnonymizer::class
                    ));
                }

                $classes[$className::getName()] = $className;
            }
        }

        $this->anonymizers = $classes;

        return $classes;
    }

    public function get(string $name): string
    {
        if (!isset($this->getAnonymizers()[$name])) {
            throw new \InvalidArgumentException(\sprintf("Can't find Anonymizer with name : %s, check your configuration.", $name));
        }

        return $this->getAnonymizers()[$name];
    }
}
