<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractEnumAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\DbToolsBundle\Helper\FileReader;

#[AsAnonymizer(
    name: 'file_enum',
    pack: 'core',
    description: <<<TXT
    Anonymize any text value using a random element from the given file.
    Options are:
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
class FileEnumAnonymizer extends AbstractEnumAnonymizer
{
    private ?string $filename = null;

    protected function getFilename(): string
    {
        if ($this->filename) {
            return $this->filename;
        }

        $filename = $this->options->getString('source', null, true);
        $filename = FileReader::ensurePathAbsolute($filename, $this->context->basePath);

        FileReader::ensureFile($filename);

        return $this->filename = $filename;
    }

    #[\Override]
    protected function validateOptions(): void
    {
        parent::validateOptions();

        $this->getFilename();
    }

    #[\Override]
    protected function getSample(): array
    {
        return \iterator_to_array(
            FileReader::readEnumFile(
                $this->getFilename(),
                $this->options,
            )
        );
    }
}
