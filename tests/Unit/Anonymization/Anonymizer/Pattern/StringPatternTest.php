<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Anonymizer\Pattern;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Pattern\StringPattern;
use PHPUnit\Framework\TestCase;

class StringPatternTest extends TestCase
{
    private function serialize(array $parts): string
    {
        return \implode("\n", \array_map(fn ($part) => (string) $part, $parts));
    }

    public function testEscape(): void
    {
        $string = new StringPattern(
            'Test [1-12] with {foo.bar} and [12-37] but [7;-12][3-6] is OK and {foo.bar:baz} is too, as {foo}, and {bla} also',
            'some_pack',
        );

        // WARNING: Please keep whitespaces.
        // See __toString() method on GeneratedPart implementations.
        self::assertSame(
            <<<EOT
                "Test "
                intrange:[1,12]
                " with "
                ref:{foo.bar:0}[0]
                " and "
                intrange:[12,37]
                " but "
                intrange:[-12,7]
                intrange:[3,6]
                " is OK and "
                ref:{foo.bar:baz}[0]
                " is too, as "
                ref:{some_pack.foo:0}[0]
                ", and "
                ref:{some_pack.bla:0}[0]
                " also"
                EOT,
            $this->serialize($string->parts),
        );
    }
}
