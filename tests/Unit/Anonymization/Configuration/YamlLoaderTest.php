<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Configuration;

use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\Loader\YamlLoader;
use MakinaCorpus\DbToolsBundle\Tests\UnitTestCase;

class YamlLoaderTest extends UnitTestCase
{
    public function testLoadOk(): void
    {
        $path = \dirname(\dirname(\dirname(__DIR__))) . '/Resources/Loader/config_ok.yaml';

        // We try to load configuration for the 'default' connection.
        $config = new AnonymizationConfig('default');
        (new YamlLoader($path))->load($config);

        // Then we validate what's in it:
        self::assertCount(2, $config->all());

        $userTableConfig = $config->getTableConfig('user');
        self::assertCount(3, $userTableConfig);

        self::assertInstanceOf(AnonymizerConfig::class, $userTableConfig['age_column']);
        self::assertSame('integer', $userTableConfig['age_column']->anonymizer);
        self::assertSame('age_column', $userTableConfig['age_column']->targetName);
        self::assertSame(0, $userTableConfig['age_column']->options->get('min'));
        self::assertSame(99, $userTableConfig['age_column']->options->get('max'));

        self::assertInstanceOf(AnonymizerConfig::class, $userTableConfig['email_column']);
        self::assertSame('email', $userTableConfig['email_column']->anonymizer);
        self::assertSame('email_column', $userTableConfig['email_column']->targetName);
        self::assertSame('toto.com', $userTableConfig['email_column']->options->get('domain'));

        self::assertInstanceOf(AnonymizerConfig::class, $userTableConfig['address']);
        self::assertSame('address', $userTableConfig['address']->anonymizer);
        self::assertSame('address', $userTableConfig['address']->targetName);
        self::assertSame('street', $userTableConfig['address']->options->get('street_address'));
        self::assertNull($userTableConfig['address']->options->get('secondary_address'));
        self::assertSame('zip_code', $userTableConfig['address']->options->get('postal_code'));
        self::assertSame('city', $userTableConfig['address']->options->get('locality'));
        self::assertNull($userTableConfig['address']->options->get('region'));
        self::assertSame('country', $userTableConfig['address']->options->get('country'));

        $user2TableConfig = $config->getTableConfig('user2');
        self::assertCount(2, $user2TableConfig);

        self::assertInstanceOf(AnonymizerConfig::class, $user2TableConfig['email_column']);
        self::assertSame('email', $user2TableConfig['email_column']->anonymizer);
        self::assertSame('email_column', $user2TableConfig['email_column']->targetName);
        self::assertNull($user2TableConfig['email_column']->options->get('domain'));
        self::assertInstanceOf(AnonymizerConfig::class, $user2TableConfig['level_column']);
        self::assertSame('string', $user2TableConfig['level_column']->anonymizer);
        self::assertSame('level_column', $user2TableConfig['level_column']->targetName);
        self::assertSame(['none', 'bad', 'good', 'expert'], $user2TableConfig['level_column']->options->get('sample'));

        // We try to load configuration for the 'not_in_the_file' connection.
        $config = new AnonymizationConfig('not_in_the_file');
        (new YamlLoader($path))->load($config);

        // Then we validate it's empty:
        self::assertCount(0, $config->all());
    }

    public function testLoadKo(): void
    {
        self::expectExceptionMessageMatches("@table 'user', key 'age_column':@");
        $path = \dirname(\dirname(\dirname(__DIR__))) . '/Resources/Loader/config_ko_no_anonymizer.yaml';
        $config = new AnonymizationConfig('default');
        (new YamlLoader($path))->load($config);

        self::expectExceptionMessageMatches("@table 'user', key 'age_column':@");
        $path = \dirname(\dirname(\dirname(__DIR__))) . '/Resources/Loader/config_ko_unknow_parameter.yaml';
        $config = new AnonymizationConfig('default');
        (new YamlLoader($path))->load($config);
    }
}
