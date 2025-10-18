<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Pack;

use MakinaCorpus\DbToolsBundle\Anonymization\Pack\Pack;
use MakinaCorpus\DbToolsBundle\Anonymization\Pack\PackEnumAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Pack\PackMultipleColumnAnonymizer;
use PHPUnit\Framework\TestCase;

class PackTest extends TestCase
{
    protected function getDirectory(?string $filename = null): string
    {
        return \dirname(__DIR__, 3) . '/Resources/Anonymization/Pack' . ($filename ? '/' . $filename : '');
    }

    public function testFromFile(): void
    {
        $pack = Pack::fromFile($this->getDirectory('pack.sample.yaml'));

        // @todo
        self::expectNotToPerformAssertions();
    }
}
