<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractMultipleColumnAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\DbToolsBundle\Error\ConfigurationException;
use MakinaCorpus\DbToolsBundle\Helper\FileReader;

#[AsAnonymizer(
    name: 'file_column',
    pack: 'core',
    description: <<<TXT
    Anonymize multiple text values using a random row from the given file.
    Options are:
      - 'columns': column names that matches file columns. If you need to
        skip one of the file columns, simply set null instead of a name.
        Please remember that other option names defined here cannot be
        column names.
      - 'source': filename to load, filename must be absolute, or relative
        to the configuration file directory.
      - 'file_csv_enclosure': if file is a CSV, use this as the enclosure
        character (default is '"').
      - 'file_csv_escape': if file is a CSV, use this as the escape
        character (default is '\\').
      - 'file_csv_separator': if file is a CSV, use this as the separator
        character (default is ',').
      - 'file_skip_header': when reading any file, set this to true to skip
        the first line (default is false).
    TXT
)]
class FileMultipleColumnAnonymizer extends AbstractMultipleColumnAnonymizer
{
    private ?string $filename = null;

    protected function getFilename(): string
    {
        if ($this->filename) {
            return $this->filename;
        }

        $filename = $this->options->getString('source', null, true);

        if ($basePath = $this->options->getString('base_path')) {
            $filename = FileReader::ensurePathAbsolute($filename, $basePath);
        }

        FileReader::ensureFile($filename);

        return $this->filename = $filename;
    }

    #[\Override]
    protected function validateOptions(): void
    {
        parent::validateOptions();

        $this->getFilename();

        $columns = $this->options->get('columns', null, true);
        if (!\is_array($columns)) {
            throw new ConfigurationException("'columns' must be an array of string or null values.");
        }
        $invalidNames = ['source', 'columns', 'file_csv_enclosure', 'file_csv_escape', 'file_csv_separator', 'file_skip_header'];
        foreach ($columns as $index => $column) {
            if (\in_array($column, $invalidNames)) {
                throw new ConfigurationException(\sprintf("'columns' values cannot be one of ('%s') for column #%d.", \implode("', '", $invalidNames), $index));
            }
            if (!\is_string($column) && null !== $column) {
                throw new ConfigurationException(\sprintf("'columns' must be an array of string or null values (invalid type for column #%d.", $index));
            }
        }
    }

    #[\Override]
    protected function getColumnNames(): array
    {
        $ret = [];

        $ignored = 0;
        foreach ($this->options->get('columns', null, true) as $name) {
            if (null === $name) {
                // It's easier to proceed this way than to strip down each
                // sample rows from the ignored columns in getSamples().
                // Even though, it would be cleaner, let's keep everything
                // simple for now.
                $ret[] = '_ignored' . ($ignored++);
            } else {
                $ret[] = $name;
            }
        }

        return $ret;
    }

    #[\Override]
    protected function getSamples(): array
    {
        return \iterator_to_array(
            FileReader::readColumnFile(
                $this->getFilename(),
                $this->options,
            ),
        );
    }
}
