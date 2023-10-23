<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Anonymizer\FrFR;

use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymizer\FrFR\PhoneNumberAnonymizer;
use MakinaCorpus\DbToolsBundle\Tests\UnitTestCase;

class PhoneNumberAnonymizerTest extends UnitTestCase
{
    public function testAnonymize(): void
    {
        $updateQuery = $this->getQueryBuilder()->update('some_table');

        $instance = new PhoneNumberAnonymizer(
            'some_table',
            'phone_column',
            $this->getConnection(),
            new Options(),
        );

        $instance->anonymize($updateQuery);

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
