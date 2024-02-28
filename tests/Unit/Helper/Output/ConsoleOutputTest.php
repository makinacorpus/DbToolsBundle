<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Helper\Output;

use MakinaCorpus\DbToolsBundle\Helper\Output\ConsoleOutput;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class ConsoleOutputTest extends TestCase
{
    public function testWrite(): void
    {
        $sfOutput = new BufferedOutput();
        $output = new ConsoleOutput($sfOutput);

        // Single line text.
        $output->write('test');
        self::assertSame('test', $sfOutput->fetch());

        // Multiple lines text.
        $output->write(
            <<<TXT
            I contain
            two line
            breaks.
            TXT
        );
        self::assertSame("I contain\ntwo line\nbreaks.", $sfOutput->fetch());
    }

    public function testWriteLine(): void
    {
        $sfOutput = new BufferedOutput();
        $output = new ConsoleOutput($sfOutput);

        // Single line text.
        $output->writeLine('test');
        self::assertSame("test\n", $sfOutput->fetch());

        $output->writeLine('test', 4);
        self::assertSame("test\n\n\n\n", $sfOutput->fetch());

        // Multiple lines text.
        $output->writeLine(
            <<<TXT
            I will contain
            three line
            breaks.
            TXT
        );
        self::assertSame("I will contain\nthree line\nbreaks.\n", $sfOutput->fetch());

        $output->writeLine(
            <<<TXT
            I will contain
            five line
            breaks.
            TXT,
            3
        );
        self::assertSame("I will contain\nfive line\nbreaks.\n\n\n", $sfOutput->fetch());
    }

    public function testNewLine(): void
    {
        $sfOutput = new BufferedOutput();
        $output = new ConsoleOutput($sfOutput);

        $output->newLine();
        self::assertSame("\n", $sfOutput->fetch());

        $output->newLine(4);
        self::assertSame("\n\n\n\n", $sfOutput->fetch());
    }

    public function testIndent(): void
    {
        $sfOutput = new BufferedOutput();
        $output = new ConsoleOutput($sfOutput);

        // Single line text.
        $output->indent();
        $output->write('test');
        self::assertSame("    test", $sfOutput->fetch());

        // Multiple lines text.
        $output->indent(2);
        $output->write(
            <<<TXT
            I will contain
            two line
            breaks.
            TXT
        );
        self::assertSame(
            <<<TXT
                        I will contain
                        two line
                        breaks.
            TXT,
            $sfOutput->fetch()
        );
    }

    #[Depends('testIndent')]
    public function testOutdent(): void
    {
        $sfOutput = new BufferedOutput();
        $output = new ConsoleOutput($sfOutput);
        $output->indent(4);
        $output->write('test');
        self::assertSame("                test", $sfOutput->fetch());

        // Single line text.
        $output->outdent();
        $output->write('test');
        self::assertSame("            test", $sfOutput->fetch());

        $output->outdent();
        $output->writeLine('test');
        self::assertSame("        test\n", $sfOutput->fetch());

        // Multiple lines text.
        $output->write(
            <<<TXT
            I will contain
            two line
            breaks.
            TXT
        );
        self::assertSame(
            <<<TXT
                    I will contain
                    two line
                    breaks.
            TXT,
            $sfOutput->fetch()
        );
    }

    public function testAllTogether(): void
    {
        $sfOutput = new BufferedOutput();

        // With default indentation size.
        $output = new ConsoleOutput($sfOutput);
        $output->writeLine('Main title');
        $output->writeLine('==========', 2);
        $output->write('A sentence of introduction with a table of contents following.');
        $output->indent();
        $output->newLine(2);
        $output->writeLine('1. Chapter 1');
        $output->indent();
        $output->writeLine(
            <<<TOC
            1.1. Sub-chapter 1
            1.2. Sub-chapter 2
            1.3. Sub-chapter 3
            TOC
        );
        $output->outdent();
        $output->writeLine('2. Chapter 2');
        $output->indent();
        $output->writeLine('2.1. Sub-chapter 1');
        $output->writeLine('2.2. Sub-chapter 2');
        $output->outdent(2);
        $output->newLine(2);
        $output->writeLine('1. Chapter 1');
        $output->writeLine('------------', 2);
        $output->write('Lorem ipsum...');

        self::assertSame(
            <<<TXT
            Main title
            ==========

            A sentence of introduction with a table of contents following.

                1. Chapter 1
                    1.1. Sub-chapter 1
                    1.2. Sub-chapter 2
                    1.3. Sub-chapter 3
                2. Chapter 2
                    2.1. Sub-chapter 1
                    2.2. Sub-chapter 2


            1. Chapter 1
            ------------

            Lorem ipsum...
            TXT,
            $sfOutput->fetch()
        );

        // With customized indentation size.
        $output = new ConsoleOutput($sfOutput, 2);
        $output->writeLine('Table of contents');
        $output->writeLine("'''''''''''''''''", 2);
        $output->indent();
        $output->writeLine('1. Chapter 1');
        $output->indent();
        $output->writeLine(
            <<<TOC
            1.1. Sub-chapter 1
            1.2. Sub-chapter 2
            1.3. Sub-chapter 3
            TOC,
            2
        );
        $output->outdent();
        $output->writeLine('2. Chapter 2');
        $output->indent();
        $output->writeLine('2.1. Sub-chapter 1');
        $output->writeLine('2.2. Sub-chapter 2');
        $output->outdent(2);
        $output->newLine();
        $output->write('Lorem ipsum...');

        self::assertSame(
            <<<TXT
            Table of contents
            '''''''''''''''''

              1. Chapter 1
                1.1. Sub-chapter 1
                1.2. Sub-chapter 2
                1.3. Sub-chapter 3

              2. Chapter 2
                2.1. Sub-chapter 1
                2.2. Sub-chapter 2

            Lorem ipsum...
            TXT,
            $sfOutput->fetch()
        );

    }
}
