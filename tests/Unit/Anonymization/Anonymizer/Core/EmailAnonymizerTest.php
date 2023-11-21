<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\EmailAnonymizer;
use MakinaCorpus\DbToolsBundle\Tests\UnitTestCase;

class EmailAnonymizerTest extends UnitTestCase
{
    public function testAnonymizeWithDefaultDomain(): void
    {
        $update = $this->getQueryBuilder()->update('some_table');

        $instance = new EmailAnonymizer(
            'some_table',
            'email',
            $this->getConnection(),
            new Options()
        );

        $instance->anonymize($update);

        $prepared = $this->prepareSql($update);

        self::assertSameSql(
            <<<SQL
            update "some_table"
            set
                "email" = case when "some_table"."email" is not null
                    then #1 || md5(cast("some_table"."email" as varchar))|| #2 || #3
                    else null
                end
            SQL,
            $prepared,
        );

        self::assertSame(
            ['anon-', '@', 'example.com'],
            $prepared->getArguments()->getAll(),
        );
    }

    public function testAnonymize(): void
    {
        $update = $this->getQueryBuilder()->update('some_table');

        $instance = new EmailAnonymizer(
            'some_table',
            'email',
            $this->getConnection(),
            new Options([
                'domain' => 'makina-corpus.com',
            ])
        );

        $instance->anonymize($update);

        $prepared = $this->prepareSql($update);

        self::assertSameSql(
            <<<SQL
            update "some_table"
            set
                "email" = case when "some_table"."email" is not null
                    then #1 || md5(cast("some_table"."email" as varchar))|| #2 || #3
                    else null
                end
            SQL,
            $update,
        );

        self::assertSame(
            ['anon-', '@', 'makina-corpus.com'],
            $prepared->getArguments()->getAll(),
        );
    }
}
