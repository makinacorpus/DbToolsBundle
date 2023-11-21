<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Anonymization\Anonymizer\FrFR;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\FrFR\PhoneNumberAnonymizer;
use MakinaCorpus\DbToolsBundle\Tests\UnitTestCase;

class PhoneNumberAnonymizerTest extends UnitTestCase
{
    public function testAnonymize(): void
    {
        $update = $this->getQueryBuilder()->update('some_table');

        $instance = new PhoneNumberAnonymizer(
            'some_table',
            'phone_column',
            $this->getConnection(),
            new Options(),
        );

        $instance->anonymize($update);

        self::assertSameSql(
            <<<SQL
            update "some_table"
            set
                "phone_column" = case
                    when "some_table"."phone_column" is not null
                        then #1 || lpad(cast(floor(random() * (cast(#2 as int) - #3 + 1) + #4) as varchar), #5, #6)
                    else null
                end
            SQL,
            $update,
        );
    }
}
