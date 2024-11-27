<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Datasource;

use MakinaCorpus\DbToolsBundle\Error\DatasourceException;

/**
 * There are two usages for the datasource:
 *
 *   - You fill a sample table: in this case, it's best to assume that the
 *     default behavior is to create a sample table which contains all the
 *     datalist. In this case, we need the datasource to be an iterator
 *     which will not consume any memory while reading the file.
 *
 *   - The second use case if when using it as an expression datasource,
 *     then we need to be able to randomly select a line in the datasource,
 *     which means we probably need to load it into memory.
 *
 * In regard of the second use case, the default implementations will always
 * load all data into memory, and we'll see what happens next.
 *
 * If this causes trouble, we might want to implement some kind of random
 * line read in files directly algorithm, it does not really seem that
 * difficult to implement.
 */
abstract class Datasource implements \Countable
{
    public function __construct(
        private string $name,
    ) {}

    /**
     * Get datasource name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get random value in.
     *
     * @return string|array<string>
     */
    public abstract function random(Context $context): string|array;

    /**
     * Get all values iterator.
     *
     * @return iterable<string>|iterable<array<string>>
     */
    public abstract function iterator(Context $context): iterable;

    /**
     * Raise an error.
     */
    protected function throwError(string|\Throwable $error): never
    {
        $prefix = \sprintf('Datasource "%s": ', $this->name);

        if ($error instanceof \Throwable) {
            throw new DatasourceException($prefix . $error->getMessage(), 0, $error);
        }
        throw new DatasourceException($prefix . $error);
    }
}
