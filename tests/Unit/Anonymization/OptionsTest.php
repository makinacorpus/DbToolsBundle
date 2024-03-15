<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    public function testHas(): void
    {
        $options = new Options([
            'foo' => "1",
            'bar' => null,
        ]);

        self::assertTrue($options->has('foo'));
        self::assertTrue($options->has('bar'), "null values are considered being set");
        self::assertFalse($options->has('baz'));
    }

    public function testAll(): void
    {
        $options = new Options([
            'foo' => "1",
            'bar' => null,
        ]);

        self::assertSame(
            [
                'foo' => "1",
                'bar' => null,
            ],
            $options->all(),
        );
    }

    public function testCount(): void
    {
        $options = new Options([
            'foo' => "1",
            'bar' => null,
        ]);

        self::assertSame(2, $options->count());
    }

    public function testGetWithDefault(): void
    {
        $options = new Options([
            'foo' => "1",
            'bar' => null,
        ]);

        self::assertSame("1", $options->get('foo', "2"));
        self::assertSame("3", $options->get('bar', "3"));
        self::assertSame(7, $options->get('bar', 7));
    }

    public function testGetWithRequired(): void
    {
        $options = new Options([
            'foo' => "1",
            'bar' => null,
        ]);

        self::assertSame("1", $options->get('foo', null, true));
        self::assertSame(null, $options->get('bar', null, true));

        // Trying to get ungiven required option with no default
        // value should lead to an exception
        self::expectExceptionMessageMatches("@value is required@");
        $options->get('baz', null, true);


        // Trying to get ungiven required option with default
        // value should not lead to an exception
        self::assertSame("2", $options->get('baz', "2", true));

    }

    public function testGetString() {
        $options = new Options([
            'ok1' => "test",
            'ok2' => 1,
            'ok3' => 12.5,
            'ok4' => new class() {
                public function __toString()
                {
                    return 'test';
                }
            },
            'ko1' => new \DateTimeImmutable(),
            'ko2' => ['test'],
        ]);

        self::assertSame("test", $options->getString('ok1'));
        self::assertSame("1", $options->getString('ok2'));
        self::assertSame("12.5", $options->getString('ok3'));
        self::assertIsString($options->getString('ok4'));

        self::expectExceptionMessageMatches("@value should be a string@");
        $options->getString('ko1');
        self::expectExceptionMessageMatches("@value should be a string@");
        $options->getString('ko2');
    }

    public function testGetInt() {
        $options = new Options([
            'ok1' => "1",
            'ok2' => 1,
            'ok3' => 12.5,
            'ko1' => new \DateTimeImmutable(),
            'ko2' => ['test'],
        ]);

        self::assertSame(1, $options->getInt('ok1'));
        self::assertSame(1, $options->getInt('ok2'));
        self::assertSame(12, $options->getInt('ok3'));

        self::expectExceptionMessageMatches("@value should be an int@");
        $options->getInt('ko1');
        self::expectExceptionMessageMatches("@value should be an int@");
        $options->getInt('ko2');

    }

    public function testGetFloat() {
        $options = new Options([
            'ok1' => "1",
            'ok2' => 1,
            'ok3' => 12.5,
            'ko1' => new \DateTimeImmutable(),
            'ko2' => ['test'],
        ]);

        self::assertSame(1.0, $options->getFloat('ok1'));
        self::assertSame(1.0, $options->getFloat('ok2'));
        self::assertSame(12.5, $options->getFloat('ok3'));

        self::expectExceptionMessageMatches("@value should be a float@");
        $options->getFloat('ko1');
        self::expectExceptionMessageMatches("@value should be a float@");
        $options->getFloat('ko2');
    }

    public function testGetDate() {
        $options = new Options([
            'ok1' => new \DateTimeImmutable(),
            'ok2' => new \DateTime(),
            'ok3' => '2 years ago',
            'ok4' => '2022-01-24',
            'ok5' => '2022-01-24 00:25',
            'ko1' => 'test',
            'ko2' => ['test'],
            'ko3' => 123456789,
        ]);

        self::assertInstanceOf(\DateTimeImmutable::class, $options->getDate('ok1'));
        self::assertInstanceOf(\DateTimeImmutable::class, $options->getDate('ok2'));
        self::assertInstanceOf(\DateTimeImmutable::class, $options->getDate('ok3'));
        self::assertInstanceOf(\DateTimeImmutable::class, $options->getDate('ok4'));
        self::assertInstanceOf(\DateTimeImmutable::class, $options->getDate('ok5'));

        self::expectExceptionMessageMatches("@could not be converted to DateTimeImmutable@");
        $options->getDate('ko1');
        self::expectExceptionMessageMatches("@could not be converted to DateTimeImmutable@");
        $options->getDate('ko2');
        self::expectExceptionMessageMatches("@could not be converted to DateTimeImmutable@");
        $options->getDate('ko3');

    }

    public function testGetInterval() {
        $options = new Options([
            'ok1' => 'P1D',
            'ok2' => 'PT36S',
            'ko1' => 'test',
            'ko2' => ['test'],
        ]);

        self::assertInstanceOf(\DateInterval::class, $options->getInterval('ok1'));
        self::assertInstanceOf(\DateInterval::class, $options->getInterval('ok2'));

        self::expectExceptionMessageMatches("@could not be converted to DateInterval@");
        $options->getInterval('ko1');
        self::expectExceptionMessageMatches("@could not be converted to DateInterval@");
        $options->getInterval('ko2');

    }

}
