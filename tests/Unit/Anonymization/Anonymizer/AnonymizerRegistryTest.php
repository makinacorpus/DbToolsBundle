<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Tests\UnitTestCase;
use Symfony\Component\Filesystem\Filesystem;

class AnonymizerRegistryTest extends UnitTestCase
{
    public function testAnonymizerRegistryWithTestPack(): void
    {
        $projectDir = $this->perpareDumbProjectDir();

        $dbToolsBundleSrcPath = \dirname(\dirname(\dirname(\dirname(__DIR__)))) . '/src/';

        $anonymizerRegistry = new AnonymizerRegistry(
            $projectDir,
            [
                $dbToolsBundleSrcPath . '/Anonymization/Anonymizer'
            ]
        );

        $anonymizers = $anonymizerRegistry->getAnonymizers();

        self::assertNotEmpty($anonymizers);
        self::assertArrayHasKey('string', $anonymizers);
        self::assertArrayHasKey('test.my-anonymizer', $anonymizers);

        self::assertEquals('MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\FloatAnonymizer', $anonymizerRegistry->get('float'));
        self::assertEquals('DbToolsBundle\PackTest\Anonymizer\MyAnonymizer', $anonymizerRegistry->get('test.my-anonymizer'));
    }

    public function testAnonymizerRegistryWithoutTestPack(): void
    {
        $projectDir = $this->perpareDumbProjectDir(false);

        $dbToolsBundleSrcPath = \dirname(\dirname(\dirname(\dirname(__DIR__)))) . '/src/';

        $anonymizerRegistry = new AnonymizerRegistry(
            $projectDir,
            [
                $dbToolsBundleSrcPath . '/Anonymization/Anonymizer'
            ]
        );

        self::assertEquals('MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\FloatAnonymizer', $anonymizerRegistry->get('float'));

        self::expectExceptionMessageMatches("@Can't find Anonymizer@");
        $anonymizerRegistry->get('test.my_anonymizer');
    }

    private function perpareDumbProjectDir(bool $withTestVendor = true): string
    {
        $projectDir = sys_get_temp_dir().'/'.uniqid('db_tools_', true);

        $filesystem = new Filesystem();

        $filesystem->mkdir($projectDir . '/vendor');
        if ($withTestVendor) {
            $filesystem->mirror(\dirname(\dirname(\dirname(__DIR__))) . '/Resources/vendor', $projectDir . '/vendor');
        }

        return $projectDir;
    }
}
