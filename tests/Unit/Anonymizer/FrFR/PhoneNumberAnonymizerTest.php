<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymizer\FrFR;

use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymizer\FrFR\PhoneNumberAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target\Column;
use MakinaCorpus\DbToolsBundle\Tests\AbstractDatabaseUnitTestCase;

class PhoneNumberAnonymizerTest extends AbstractDatabaseUnitTestCase
{
    public function testAnonymize(): void
    {
        $updateQuery = $this->getQueryBuilder()->update('some_table');

        $instance = new PhoneNumberAnonymizer($this->getConnection());

        $instance->anonymize($updateQuery, new Column('some_table', 'phone_column'), new Options());

        self::assertSameSql(
            <<<SQL
            update some_table
            set
                "phone_column" = case
                    when "phone_column" is not null then '063998' || lpad(
                        cast(
                            cast(
                                random() * (9999 - 0 + 1)
                                as int
                            )
                            as text
                        ),
                        4,
                        '0'
                    )
                end
            SQL,
            $updateQuery,
        );
    }
}
