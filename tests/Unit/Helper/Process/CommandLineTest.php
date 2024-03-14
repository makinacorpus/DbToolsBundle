<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Helper\Process;

use MakinaCorpus\DbToolsBundle\Helper\Process\CommandLine;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\TestCase;

#[RequiresOperatingSystem('Linux')]
class CommandLineTest extends TestCase
{
    private ?\ReflectionClass $rc = null;

    protected function setUp(): void
    {
        if (null === $this->rc) {
            $this->rc = new \ReflectionClass(CommandLine::class);
        }
    }

    private function extractArgs(CommandLine $obj): array
    {
        return $this->rc->getProperty('args')->getValue($obj);
    }

    public function testConstructor(): void
    {
        $r = new \ReflectionClass(CommandLine::class);

        $cl = new CommandLine();
        self::assertSame([], $this->extractArgs($cl));

        $cl = new CommandLine('test');
        self::assertSame(["'test'"], $this->extractArgs($cl));

        $cl = new CommandLine('test', false);
        self::assertSame(["test"], $this->extractArgs($cl));

        $cl = new CommandLine(['test', 3, '-x', 42.24, '--opt=val', '-m', null]);
        self::assertSame(
            ["'test'", "'3'", "'-x'", "'42.24'", "'--opt=val'", "'-m'", '""'],
            $this->extractArgs($cl)
        );

        $cl = new CommandLine(['test', 3, '-x', 42.24, '--opt=val', '-m', null], false);
        self::assertSame(
            ['test', '3', '-x', '42.24', '--opt=val', '-m'],
            $this->extractArgs($cl)
        );
    }

    public function testAddArg(): void
    {
        $cl = new CommandLine();
        $cl
            ->addArg('test')
            ->addArg(42)
            ->addArg(42.24)
            ->addArg(null)
        ;
        self::assertSame(
            ["'test'", "'42'", "'42.24'", '""'],
            $this->extractArgs($cl)
        );

        $cl = new CommandLine();
        $cl->addArg('/bin/test', '-x', 42.24, '--opt=val', '-m', null, 21);
        self::assertSame(
            ["'/bin/test'", "'-x'", "'42.24'", "'--opt=val'", "'-m'", '""', "'21'"],
            $this->extractArgs($cl)
        );

        $cl = new CommandLine('/bin/test -xyz', false);
        $cl->addArg('--opt1', 'val');
        $cl->addArg('--opt2', "let's add spaces and quotes");
        self::assertSame(
            ["/bin/test -xyz", "'--opt1'", "'val'", "'--opt2'", "'let'\''s add spaces and quotes'"],
            $this->extractArgs($cl)
        );

        $cl = new CommandLine(['/bin/test', '-xyz']);
        $cl->addArg('--opt', 42);
        self::assertSame(
            ["'/bin/test'", "'-xyz'", "'--opt'", "'42'"],
            $this->extractArgs($cl)
        );
    }

    public function testAddRaw(): void
    {
        $cl = new CommandLine('/bin/test');
        $cl->addRaw('--opt val -xyz -A 1 -b 2 -c ""');
        self::assertSame(
            ["'/bin/test'", '--opt val -xyz -A 1 -b 2 -c ""'],
            $this->extractArgs($cl)
        );

        $cl = new CommandLine(['/bin/test', '-xyz'], false);
        $cl
            ->addRaw('--test 42.24 --opt=val')
            ->addRaw('-m "let\'s add spaces and quotes"')
        ;
        self::assertSame(
            ['/bin/test', '-xyz', '--test 42.24 --opt=val', '-m "let\'s add spaces and quotes"'],
            $this->extractArgs($cl)
        );
    }

    public function testToString(): void
    {
        $cl = new CommandLine();
        $cl
            ->addArg('test')
            ->addArg(42)
            ->addArg(42.24)
            ->addArg(null)
        ;
        self::assertSame(
            "exec 'test' '42' '42.24' \"\"",
            $cl->toString()
        );

        $cl = new CommandLine('/bin/test -xyz', false);
        $cl->addArg('--opt1', 'val');
        $cl->addArg('--opt2', "let's add spaces and quotes");
        self::assertSame(
            "exec /bin/test -xyz '--opt1' 'val' '--opt2' 'let'\''s add spaces and quotes'",
            $cl->toString()
        );

        $cl = new CommandLine(['/bin/test', '-xyz']);
        $cl
            ->addRaw('--test 42.24 --opt=val')
            ->addRaw('-m "let\'s add spaces and quotes"')
        ;
        self::assertSame(
            "exec '/bin/test' '-xyz' --test 42.24 --opt=val -m \"let's add spaces and quotes\"",
            $cl->toString()
        );
    }
}
