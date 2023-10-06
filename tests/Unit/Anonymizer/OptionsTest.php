<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
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
        self::assertFalse($options->has('bar'), "null values are not considered being set");
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
}
