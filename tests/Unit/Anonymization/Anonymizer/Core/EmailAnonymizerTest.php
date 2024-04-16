<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core\EmailAnonymizer;
use MakinaCorpus\DbToolsBundle\Test\UnitTestCase;

class EmailAnonymizerTest extends UnitTestCase
{
    public function testValidateOptionsOkWithNoOption(): void
    {
        new EmailAnonymizer(
            'some_table',
            'email',
            $this->getDatabaseSession(),
            new Options([]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsOkWithDomainOptionAsString(): void
    {
        new EmailAnonymizer(
            'some_table',
            'email',
            $this->getDatabaseSession(),
            new Options([
                'domain' => 'makina-corpus.com',
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsKoWithDomainNotStringable(): void
    {
        self::expectExceptionMessageMatches("@string@");

        new EmailAnonymizer(
            'some_table',
            'email',
            $this->getDatabaseSession(),
            new Options([
                'domain' => ['ttt', 'ttt'],
            ]),
        );
    }

    public function testValidateOptionsOkWithUseSaltOptionAsBool(): void
    {
        new EmailAnonymizer(
            'some_table',
            'email',
            $this->getDatabaseSession(),
            new Options([
                'use_salt' => true,
            ]),
        );

        self::expectNotToPerformAssertions();
    }

    public function testValidateOptionsKoWithUseSaltOptionAsNoneBool(): void
    {
        self::expectExceptionMessageMatches("@scalar@");

        new EmailAnonymizer(
            'some_table',
            'email',
            $this->getDatabaseSession(),
            new Options([
                'use_salt' => ['true'],
            ]),
        );
    }

    public function testAnonymizeWithDefaultDomain(): void
    {
        $update = $this->getQueryBuilder()->update('some_table');

        $instance = new EmailAnonymizer(
            'some_table',
            'email',
            $this->getDatabaseSession(),
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
                "email" = case when "some_table"."email" is not null
                    then #1 || md5("some_table"."email" || #2) || #3 || #4
                    else null
                end
            SQL,
            $prepared,
        );

        self::assertSame(
            ['anon-', 'my_salt', '@', 'example.com'],
            $prepared->getArguments()->getAll(),
        );
    }

    public function testAnonymize(): void
    {
        $update = $this->getQueryBuilder()->update('some_table');

        $instance = new EmailAnonymizer(
            'some_table',
            'email',
            $this->getDatabaseSession(),
            new Options([
                'domain' => 'makina-corpus.com',
                'salt' => 'my_salt',
            ]),
        );

        $instance->anonymize($update);

        $prepared = $this->prepareSql($update);

        self::assertSameSql(
            <<<SQL
            update "some_table"
            set
                "email" = case when "some_table"."email" is not null
                    then #1 || md5("some_table"."email" || #2) || #3 || #4
                    else null
                end
            SQL,
            $update,
        );

        self::assertSame(
            ['anon-', 'my_salt', '@', 'makina-corpus.com'],
            $prepared->getArguments()->getAll(),
        );
    }

    public function testAnonymizeWithoutSalt(): void
    {
        $update = $this->getQueryBuilder()->update('some_table');

        $instance = new EmailAnonymizer(
            'some_table',
            'email',
            $this->getDatabaseSession(),
            new Options([
                'use_salt' => false,
            ]),
        );

        $instance->anonymize($update);

        $prepared = $this->prepareSql($update);

        self::assertSameSql(
            <<<SQL
            update "some_table"
            set
                "email" = case when "some_table"."email" is not null
                    then #1 || md5("some_table"."email") || #2 || #3
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
}
