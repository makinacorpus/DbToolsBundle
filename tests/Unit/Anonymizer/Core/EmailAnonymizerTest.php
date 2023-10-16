<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymizer\Core\EmailAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target\Column;
use MakinaCorpus\DbToolsBundle\Tests\UnitTestCase;

class EmailAnonymizerTest extends UnitTestCase
{
    public function testAnonymizeWithDefaultDomain(): void
    {
        $updateQuery = $this->getQueryBuilder()->update('some_table');

        $instance = new EmailAnonymizer($this->getConnection());

        $instance->anonymize($updateQuery, new Column('some_table', 'email'), new Options());

        self::assertSameSql(
            <<<SQL
            update some_table
            set
                "email" = 'anon-' || md5("email") || '@' || 'example.com'
            SQL,
            $updateQuery,
        );
    }

    public function testAnonymize(): void
    {
        $updateQuery = $this->getQueryBuilder()->update('some_table');

        $instance = new EmailAnonymizer($this->getConnection());

        $instance->anonymize($updateQuery, new Column('some_table', 'email'), new Options([
            'domain' => 'makina-corpus.com',
        ]));

        self::assertSameSql(
            <<<SQL
            update some_table
            set
                "email" = 'anon-' || md5("email") || '@' || 'makina-corpus.com'
            SQL,
            $updateQuery,
        );
    }
}
