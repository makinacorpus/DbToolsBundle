<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Error;

class MissingDependencyException extends \RuntimeException
{
    /**
     * Create new missing dependency exception.
     */
    public static function create(string $package, ?string $className = null): self
    {
        if ($className) {
            $message = \sprintf("'%s' dependency is missing in order to use '%s' class, please consider running 'composer require %s'", $package, $className, $package);
        } else {
            $message = \sprintf("'%s' dependency is missing, please consider running 'composer require %s'", $package, $package);
        }

        return new self($message);
    }

    /**
     * Check class exists, raise missing dependency exception if not.
     */
    public static function check(string $package, string $className): void
    {
        if (!\class_exists($className)) {
            throw self::create($package, $className);
        }
    }
}
