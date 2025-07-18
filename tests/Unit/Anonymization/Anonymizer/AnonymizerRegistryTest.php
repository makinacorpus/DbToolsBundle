<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Anonymizer;

use Composer\InstalledVersions;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Test\UnitTestCase;

class AnonymizerRegistryTest extends UnitTestCase
{
    public function testAnonymizerRegistryWithTestPack(): void
    {
        try {
            $this->composerProjectAdd();

            $anonymizerRegistry = new AnonymizerRegistry();

            $anonymizers = $anonymizerRegistry->getAllAnonymizerMetadata();

            self::assertNotEmpty($anonymizers);
            self::assertArrayHasKey('string', $anonymizers);
            self::assertArrayHasKey('test.my-anonymizer', $anonymizers);

            self::assertEquals('MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\FloatAnonymizer', $anonymizerRegistry->getAnonymizerClass('float'));
            self::assertEquals('float', $anonymizerRegistry->getAnonymizerMetadata('float')->id());

            self::assertEquals('DbToolsBundle\PackTest\Anonymizer\MyAnonymizer', $anonymizerRegistry->getAnonymizerClass('test.my-anonymizer'));
            self::assertEquals('test.my-anonymizer', $anonymizerRegistry->getAnonymizerMetadata('test.my-anonymizer')->id());
        } finally {
            $this->composerProjectRemove();
        }
    }

    public function testAnonymizerRegistryWithoutTestPack(): void
    {
        $anonymizerRegistry = new AnonymizerRegistry();

        self::assertEquals('MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\FloatAnonymizer', $anonymizerRegistry->getAnonymizerClass('float'));

        self::expectExceptionMessageMatches("@Can't find Anonymizer@");
        $anonymizerRegistry->getAnonymizerClass('test.my_anonymizer');
    }

    private function composerProjectRemove(): void
    {
        $installed = InstalledVersions::getAllRawData();

        // @phpstan-ignore-next-line
        unset($installed[0]['vendor-name/pack-example']);

        InstalledVersions::reload($installed[0]);
    }

    private function composerProjectAdd(): void
    {
        $installed = InstalledVersions::getAllRawData();

        $installed[0]['versions']['vendor-name/pack-example'] = [
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => '84a55833baf4d2ce3efc1ee302d7acc8d9253c4b',
            'type' => 'db-tools-bundle-pack',
            'install_path' => \dirname(__DIR__, 3) . '/Resources/vendor/vendor-name/pack-example',
            'aliases' => [],
            'dev_requirement' => false,
        ];

        InstalledVersions::reload($installed[0]);
    }
}
