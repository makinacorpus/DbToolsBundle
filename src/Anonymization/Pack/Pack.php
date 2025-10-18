<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Pack;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Pattern\StringPattern;
use MakinaCorpus\DbToolsBundle\Error\ConfigurationException;
use MakinaCorpus\DbToolsBundle\Helper\FileReader;
use Symfony\Component\Yaml\Yaml;

class Pack
{
    /** @var array<string, PackAnonymizer> */
    private array $anonymizers;

    public function __construct(
        public readonly string $id,
        public readonly string $directory,
    ) {}

    /** @internal Add anonymizer during pack definition parsing. */
    public function addPackAnonymizer(PackAnonymizer $anonymizer): void
    {
        $this->anonymizers[$anonymizer->id] = $anonymizer;
    }

    /** Get anonymizer description. */
    public function getPackAnonymizer(string $id): PackAnonymizer
    {
        return $this->anonymizers[$id] ?? throw new ConfigurationException(\sprintf("Anonymizer '%s.%s' does not exist.", $this->id, $id));
    }

    /**
     * Create pack from file.
     */
    public static function fromFile(string $filename): Pack
    {
        $input = match (FileReader::getFileExtension($filename)) {
            'yaml' => Yaml::parseFile($filename),
            default => throw new ConfigurationException("Unsupported pack file type."),
        };

        return self::fromArray($input, \dirname($filename));
    }

    /**
     * Create pack from array input.
     */
    public static function fromArray(array $input, string $directory): Pack
    {
        if (empty($input['name']) || !\is_string($input['name'])) {
            throw new ConfigurationException(\sprintf("Missing 'name' property in pack description in folder: %s.", $directory));
        }
        if (empty($input['data']) || !\is_array($input['data'])) {
            throw new ConfigurationException(\sprintf("Missing or empty 'data' property in pack description in folder: %s.", $directory));
        }

        $ret = new Pack($input['name'], $directory);

        foreach ($input['data'] as $id => $anonymizerInput) {
            $ret->addPackAnonymizer(self::createAnonymizerFromArray($ret->id, $directory, $id, $anonymizerInput));
        }

        return $ret;
    }

    /**
     * Create pack anonymizer from array input.
     */
    private static function createAnonymizerFromArray(string $packId, string $directory, string $id, array $input): PackAnonymizer
    {
        $completeId = $packId . '.' . $id;

        $options = new Options();

        $fileOptions = [
            'file_csv_enclosure' => $input['file_csv_enclosure'] ?? '"',
            'file_csv_escape' => $input['file_csv_escape'] ?? '\\',
            'file_csv_separator' => $input['file_csv_separator'] ?? ',',
            'file_skip_header' => $input['file_skip_header'] ?? false,
        ];

        // First parse user documentation.
        $description = null;
        if (!empty($input['description'])) {
            if (!\is_string($input['description'])) {
                throw new ConfigurationException(\sprintf("Anonymizer '%s' property 'description' must be a string value.", $completeId));
            }
            $description = $input['description'];
        }

        // Discriminate column based anonymizers versus single value enums.
        $columns = null;
        if (!empty($input['columns'])) {
            if (!\is_array($input['columns'])) {
                throw new ConfigurationException(\sprintf("Anonymizer '%s' property 'columns' must be an array of string values.", $completeId));
            }
            $columns = [];
            foreach ($input['columns'] as $column) {
                if (null !== $column && !\is_string($column)) {
                    throw new ConfigurationException(\sprintf("Anonymizer '%s' property 'columns' must be an array of string values.", $completeId));
                }
                $columns[] = $column;
            }
        }

        // Plain raw data cannot be patterns, simply read from file.
        if (isset($input['data'])) {
            if (isset($input['pattern'])) {
                throw new ConfigurationException(\sprintf("Anonymizer '%s' cannot have both 'data' and 'pattern' property.", $completeId));
            }

            if (\is_string($input['data'])) {
                $filename = \rtrim($directory, '/') . '/' . \ltrim($input['data'], '/');
                if ($columns) {
                    return new PackFileMultipleColumnAnonymizer(
                        $completeId,
                        $description,
                        $options->with($fileOptions),
                        $columns,
                        $filename,
                    );
                }

                return new PackFileEnumAnonymizer(
                    $completeId,
                    $description,
                    $options->with($fileOptions),
                    $filename,
                );
            }

            if (\is_array($input['data'])) {
                if ($columns) {
                    return new PackMultipleColumnAnonymizer(
                        $completeId,
                        $description,
                        $options,
                        $columns,
                        $input['data'],
                    );
                }

                return new PackEnumAnonymizer(
                    $completeId,
                    $description,
                    $options,
                    $input['data'],
                );
            }

            throw new ConfigurationException(\sprintf("Anonymizer '%s' property 'data' must be a string file path or an array of values.", $completeId));
        }

        // Pattern data, using string patterns.
        if (isset($input['pattern'])) {
            if ($columns) {
                if (!\is_array($input['pattern'])) {
                    throw new ConfigurationException(\sprintf("Anonymizer '%s' property 'pattern' must be array of values.", $completeId));
                }

                if (\count($input['pattern']) !== \count($columns)) {
                    throw new ConfigurationException(\sprintf("Anonymizer '%s' property 'pattern' value count must be the same as the 'columns' value count.", $completeId));
                }

                // Normalize values from the "pattern" array, by creating
                // StringPattern instances, also do input sanity cleanup
                // by removing array keys, to match columns.
                $patterns = [];
                foreach ($input['pattern'] as $index => $value) {
                    if (null !== $value && !\is_string($value)) {
                        throw new ConfigurationException(\sprintf("Anonymizer '%s' property 'pattern' value at index #%s must be a string or null.", $completeId, $index));
                    }
                    $patterns[] = $value ? new StringPattern($value, $packId) : null;
                }

                return new PackMultipleColumnGeneratedAnonymizer(
                    $completeId,
                    $description,
                    $options,
                    $columns,
                    $patterns,
                );
            }

            $patterns = [];
            if (\is_string($input['pattern'])) {
                $patterns[] = new StringPattern($input['pattern'], $packId);
            } elseif (\is_array($input['pattern'])) {
                foreach ($input['pattern'] as $index => $value) {
                    if (null !== $value && !\is_string($value)) {
                        throw new ConfigurationException(\sprintf("Anonymizer '%s' property 'pattern' value at index #%s must be a string or null.", $completeId, $index));
                    }
                    $patterns[] = $value ? new StringPattern($value, $packId) : null;
                }
            } else {
                throw new ConfigurationException(\sprintf("Anonymizer '%s' property 'pattern' must be a string or an array of string.", $completeId));
            }

            return new PackEnumGeneratedAnonymizer(
                $completeId,
                $description,
                $options,
                $patterns,
            );
        }

        throw new ConfigurationException(\sprintf("Anonymizer '%s' must have one of 'data' or 'pattern' property.", $completeId));
    }
}
