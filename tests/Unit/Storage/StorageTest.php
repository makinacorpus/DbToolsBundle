<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Storage;

use MakinaCorpus\DbToolsBundle\Storage\DefaultFilenameStrategy;
use MakinaCorpus\DbToolsBundle\Storage\Storage;
use MakinaCorpus\DbToolsBundle\Tests\Unit\Storage\Mock\OutOfRootFilenameStrategy;
use MakinaCorpus\DbToolsBundle\Tests\Unit\Storage\Mock\TestFilenameStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class StorageTest extends TestCase
{
    private ?string $rootDir = null;
    private ?string $outsideOfRootDir = null;

    #[\Override]
    protected function setUp(): void
    {
        $this->rootDir = \sys_get_temp_dir() . '/db-tools-bundle-test/root-dir';
        $this->outsideOfRootDir = \sys_get_temp_dir() . '/db-tools-bundle-test/outside-root-dir';

        (new Filesystem())->mkdir($this->rootDir);
    }

    #[\Override]
    protected function tearDown(): void
    {
        (new Filesystem())->remove(\sys_get_temp_dir() . '/db-tools-bundle-test');
    }

    public function testGenerateFilenameUsesDefaultWhenUnspecified(): void
    {
        $storage = new Storage($this->rootDir, 'now -6 month');

        self::assertMatchesRegularExpression(
            '@^' . \preg_quote($this->rootDir) . '/foo/\d{1,4}/\d{1,2}/foo-\d{6,14}\.some_ext$@',
            $storage->generateFilename('foo', 'some_ext'),
        );
    }

    public function testGenerateFilenameUsesStrategy(): void
    {
        $storage = new Storage($this->rootDir, 'now -6 month', [
            'bar' => new TestFilenameStrategy(),
        ]);

        self::assertNotSame(
            $this->rootDir . '/bar/foo.sql',
            $storage->generateFilename('foo', 'sql'),
        );

        self::assertSame(
            $this->rootDir . '/bar/bar.sql',
            $storage->generateFilename('bar', 'sql'),
        );
    }

    public function testListBackups(): void
    {
        $storage = new Storage($this->rootDir, 'now -6 month', [
            'outside' => new OutOfRootFilenameStrategy($this->outsideOfRootDir),
            'inside' => new DefaultFilenameStrategy(),
            'another' => new DefaultFilenameStrategy(),
        ]);

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->rootDir . '/other/foo/bar');
        $filesystem->touch($this->rootDir . '/other/foo/bar/beh.sql');
        $filesystem->mkdir($this->rootDir . '/inside/foo/bar');
        $filesystem->touch($this->rootDir . '/inside/foo/bar/baz.sql');
        $filesystem->touch($this->rootDir . '/inside/foo/bar/bla.sql');
        $filesystem->mkdir($this->outsideOfRootDir . '/fizz/buzz');
        $filesystem->touch($this->outsideOfRootDir . '/fizz/buzz/a.sql');
        $filesystem->touch($this->outsideOfRootDir . '/fizz/buzz/b.sql');

        self::assertSame(
            [
                '/tmp/db-tools-bundle-test/root-dir/inside/foo/bar/baz.sql',
                '/tmp/db-tools-bundle-test/root-dir/inside/foo/bar/bla.sql'
            ],
            \array_map(fn (array $data) => $data[1]->getPathname(), $storage->listBackups('inside')),
        );

        self::assertSame(
            [
                '/tmp/db-tools-bundle-test/outside-root-dir/fizz/buzz/a.sql',
                '/tmp/db-tools-bundle-test/outside-root-dir/fizz/buzz/b.sql'
            ],
            \array_map(fn (array $data) => $data[1]->getPathname(), $storage->listBackups('outside')),
        );
    }
}
