<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\Md5Anonymizer;
use MakinaCorpus\DbToolsBundle\Test\UnitTestCase;

class Md5AnonymizerTest extends UnitTestCase
{
    public function testAnonymize(): void
    {
        $update = $this->getQueryBuilder()->update('some_table');

        $instance = new Md5Anonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([
                'salt' => 'my_salt',
            ])
        );

        $instance->anonymize($update);

        $prepared = $this->prepareSql($update);

        self::assertSameSql(
            <<<SQL
            update "some_table"
            set
                "some_column" = case
                    when "some_table"."some_column" is not null
                        then md5("some_table"."some_column" || #1)
                    else null
                end
            SQL,
            $update,
        );

        self::assertSame(
            ['my_salt'],
            $prepared->getArguments()->getAll(),
        );
    }

    public function testAnonymizeWithoutSalt(): void
    {
        $update = $this->getQueryBuilder()->update('some_table');

        $instance = new Md5Anonymizer(
            'some_table',
            'some_column',
            $this->getConnection(),
            new Options([
                'use_salt' => false,
            ])
        );

        $instance->anonymize($update);

        $prepared = $this->prepareSql($update);

        self::assertSameSql(
            <<<SQL
            update "some_table"
            set
                "some_column" = md5("some_table"."some_column")
            SQL,
            $update,
        );

        self::assertSame(
            [],
            $prepared->getArguments()->getAll(),
        );
    }
}
