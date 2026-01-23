<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Helper\Pack;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Helper\FileReader;
use PHPUnit\Framework\TestCase;

class FileReaderTest extends TestCase
{
    protected function getDirectory(?string $filename = null): string
    {
        return \dirname(__DIR__, 2) . '/Resources/Anonymization/Pack' . ($filename ? '/' . $filename : '');
    }

    public function testReadEnumFileTxt(): void
    {
        $data = FileReader::readEnumFile($this->getDirectory('resources/enum-file.txt'));

        self::assertSame(
            ['foo', 'a', '1'],
            \iterator_to_array($data),
        );
    }

    public function testReadEnumFileSkipHeader(): void
    {
        $data = FileReader::readEnumFile($this->getDirectory('resources/enum-file-header.txt'), new Options([
            'file_skip_header' => true,
        ]));

        self::assertSame(
            ['foo', 'a', '1'],
            \iterator_to_array($data),
        );
    }

    public function testReadEnumFileCsv(): void
    {
        $data = FileReader::readEnumFile($this->getDirectory('resources/enum-file.csv'));

        self::assertSame(
            ['foo', 'a', '1', 'cat'],
            \iterator_to_array($data),
        );
    }

    public function testReadEnumFileCsvSkipHeader(): void
    {
        $data = FileReader::readEnumFile($this->getDirectory('resources/enum-file-header.csv'), new Options([
            'file_skip_header' => true,
        ]));

        self::assertSame(
            ['a', '1'],
            \iterator_to_array($data),
        );
    }

    public function testReadColumnFileCsv(): void
    {
        $data = FileReader::readColumnFile($this->getDirectory('resources/enum-file.csv'));

        self::assertSame(
            [
                ['foo', 'bar', 'baz'],
                ['a', 'b', 'c'],
                ['1', '2', '3'],
                ['cat', 'dog', 'girafe'],
            ],
            \iterator_to_array($data),
        );
    }

    public function testReadColumnFileTsv(): void
    {
        $data = FileReader::readColumnFile($this->getDirectory('resources/enum-file.tsv'), new Options([
            'file_csv_enclosure' => "'",
            'file_csv_escape' => '\\',
            'file_csv_separator' => "#",
        ]));

        self::assertSame(
            [
                ['foo', 'bar', 'baz'],
                ['cat', 'dog', 'gi\#rafe'],
            ],
            \iterator_to_array($data),
        );
    }
}
