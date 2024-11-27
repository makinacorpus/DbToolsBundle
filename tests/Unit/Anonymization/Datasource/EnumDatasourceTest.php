<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Datasource;

use PHPUnit\Framework\TestCase;
use MakinaCorpus\DbToolsBundle\Anonymization\Datasource\EnumDatasource;

class EnumDatasourceTest extends TestCase
{
    private function getFilename(string $filename): string
    {
        return \dirname(__DIR__, 3) . '/Resources/datasource/' . $filename;
    }

    public function testCreateWithArray(): void
    {
        $datasource = new EnumDatasource('foo', ['Mathieu', 'Coraline']);

        self::assertSame('Mathieu', $datasource->rawAt(0));
        self::assertSame('Coraline', $datasource->rawAt(1));
        self::assertSame(2, $datasource->count());
    }

    public function testCreateWithNonExistingFileError(): void
    {
        $datasource = new EnumDatasource('foo', $this->getFilename('non_existing_file.txt'));

        self::expectExceptionMessageMatches('/Datasource .* file .*: does not exist./');
        $datasource->rawRandom();
    }

    public function testCreateWithJson(): void
    {
        $datasource = new EnumDatasource('foo', ['Mathieu', 'Coraline']);

        self::assertSame('Mathieu', $datasource->rawAt(0));
        self::assertSame('Coraline', $datasource->rawAt(1));
        self::assertSame(2, $datasource->count());
    }

    public function testCreateWithJsonError(): void
    {
        $datasource = new EnumDatasource('foo', $this->getFilename('invalid.json'));

        self::expectExceptionMessageMatches('/Datasource .* file .*: item #2 is not a string./');
        $datasource->rawRandom();
    }

    public function testCreateWithText(): void
    {
        $datasource = new EnumDatasource('foo', $this->getFilename('firstname.txt'));

        self::assertSame('Robert', $datasource->rawAt(0));
        self::assertSame('Arletta', $datasource->rawAt(6));
        self::assertSame(7, $datasource->count());
    }
}
