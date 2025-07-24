<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper;

use MakinaCorpus\DbToolsBundle\Error\ConfigurationException;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;

class FileReader
{
    public static function getFileExtension(string $filename): ?string
    {
        $ext = null;
        if ($pos = \strrpos($filename, '.')) {
            $ext = \substr($filename, $pos + 1);
        }

        return $ext;
    }

    /**
     * If given path is relative, and $basePath was provided, return make it
     * absolute using $basePath as directory prefix.
     */
    public static function ensurePathAbsolute(string $filename, ?string $basePath): string
    {
        // Skip schemes such as 'http://' and let stream wrappers work with it.
        // But treat the 'file://' scheme as being local files.
        if (\str_starts_with($filename, 'file://')) {
            $filename = \substr($filename, 7);
        } elseif (\str_contains($filename, '://')) {
            return $filename;
        }
        if (\str_starts_with($filename, './')) {
            $filename = \substr($filename, 2);
        }
        if (null === $basePath) {
            return $filename;
        }
        // Sorry but Windows is not supported.
        if (!\str_starts_with($filename, '/')) {
            return \rtrim($basePath, '/') . '/' . $filename;
        }
        return $filename;
    }

    /**
     * Iterator on file contents.
     *
     * @return iterable<string>
     */
    public static function readEnumFile(string $filename, ?Options $options = null, ?string $anonymizerId = null): iterable
    {
        $ext = self::getFileExtension($filename);

        // no match() usage here because CSV cannot expressed as a single expression.
        if (null === $ext || 'txt' === $ext) {
            yield from self::readTxtFile($filename, $options, $anonymizerId);
        } elseif ('csv' === $ext || 'tsv' === $ext) {
            foreach (self::readCsvFile($filename, $options, $anonymizerId) as $line) {
                \assert(\is_array($line));
                if ($line) {
                    yield $line[0];
                }
            }
        } elseif ($anonymizerId) {
            throw new ConfigurationException(\sprintf("Anonymizer '%s': unsupported enum data file type: '%s'.", $anonymizerId, $ext));
        } else {
            throw new ConfigurationException(\sprintf("Unsupported enum data file type: '%s'.", $ext));
        }
    }

    /**
     * Iterator on column file contents.
     *
     * @return iterable<array<string>>
     */
    public static function readColumnFile(string $filename, ?Options $options = null, ?string $anonymizerId = null): iterable
    {
        $ext = self::getFileExtension($filename);

        // no match() usage here because CSV cannot expressed as a single expression.
        if ('csv' === $ext || 'tsv' === $ext) {
            yield from self::readCsvFile($filename, $options, $anonymizerId);
        } else {
            throw new ConfigurationException("Unsupported column data file type.");
        }
    }

    /**
     * Iterator on plain text file lines.
     *
     * @return iterable<string>
     */
    public static function readTxtFile(string $filename, ?Options $options = null, ?string $anonymizerId = null): iterable
    {
        self::ensureFile($filename, $anonymizerId);

        $options ??= new Options();

        $handle = null;
        try {
            $handle = \fopen($filename, 'r');

            if (false === $handle) {
                if ($anonymizerId) {
                    throw new ConfigurationException(\sprintf("Anonymizer '%s' could not open file: %s", $anonymizerId, $filename));
                } else {
                    throw new ConfigurationException(\sprintf("Could not open file: %s", $filename));
                }
            }

            $first = true;
            while ($line = \fgets($handle)) {
                $line = \trim($line); // Trim whitespaces (including end of line).

                if ($first) {
                    $first = false;
                    if ($options->getBool('file_skip_header', false)) {
                        continue; // Skip header.
                    }
                }

                if (empty($line)) {
                    continue; // Empty line, ignore.
                }

                yield $line;
            }
        } finally {
            if ($handle) {
                @\fclose($handle);
            }
        }
    }

    /**
     * Iterator on CSV file contents.
     *
     * @return iterable<array<string>>
     */
    public static function readCsvFile(string $filename, ?Options $options = null, ?string $anonymizerId = null): iterable
    {
        self::ensureFile($filename, $anonymizerId);

        $options ??= new Options();

        $handle = null;
        try {
            $handle = \fopen($filename, 'r');

            if (false === $handle) {
                if ($anonymizerId) {
                    throw new ConfigurationException(\sprintf("Anonymizer '%s' could not open file: %s", $anonymizerId, $filename));
                } else {
                    throw new ConfigurationException(\sprintf("Could not open file: %s", $filename));
                }
            }

            $separator = $options->getString('file_csv_separator', ',');
            $enclosure = $options->getString('file_csv_enclosure', '"');
            $escape = $options->getString('file_csv_escape', '\\');

            $first = true;
            while ($line = \fgetcsv($handle, null, $separator, $enclosure, $escape)) {
                if ($first) {
                    $first = false;
                    if ($options->getBool('file_skip_header', false)) {
                        continue; // Skip header.
                    }
                }

                if (!\array_filter($line)) {
                    continue; // Empty line, ignore.
                }

                yield $line;
            }
        } finally {
            if ($handle) {
                @\fclose($handle);
            }
        }
    }

    public static function ensureFile(string $filename, ?string $anonymizerId = null): void
    {
        if (!\file_exists($filename)) {
            if ($anonymizerId) {
                throw new ConfigurationException(\sprintf("Anonymizer '%s' uses a non existing file: %s", $anonymizerId, $filename));
            } else {
                throw new ConfigurationException(\sprintf("Uses a non existing file: %s", $filename));
            }
        }
        if (!\is_file($filename)) {
            if ($anonymizerId) {
                throw new ConfigurationException(\sprintf("Anonymizer '%s' is not a regular file: %s", $anonymizerId, $filename));
            } else {
                throw new ConfigurationException(\sprintf("Is not a regular file: %s", $filename));
            }
        }
        if (!\is_readable($filename)) {
            if ($anonymizerId) {
                throw new ConfigurationException(\sprintf("Anonymizer '%s' file cannot be read: %s", $anonymizerId, $filename));
            } else {
                throw new ConfigurationException(\sprintf("File cannot be read: %s", $filename));
            }
        }
    }
}
