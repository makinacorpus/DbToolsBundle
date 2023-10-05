<?php

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Helper;

use MakinaCorpus\DbToolsBundle\Helper\Iban;
use MakinaCorpus\DbToolsBundle\Tests\Mock\TestingExecutionContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Iban as IbanConstraint;
use Symfony\Component\Validator\Constraints\IbanValidator;

class IbanTest extends TestCase
{
    public static function dataSupportedCountryCode()
    {
        return \array_map(fn ($value) => [$value], Iban::supportedCountries());
    }

    /**
     * @dataProvider dataSupportedCountryCode()
     */
    public function testGenerateAllCountries(string $countryCode): void
    {
        $validator = new IbanValidator();
        $constraint = new IbanConstraint();

        $generated = Iban::iban($countryCode);

        $context = new TestingExecutionContext();
        $validator->initialize($context);
        $validator->validate($generated, $constraint);

        self::assertTrue(
            !$context->getViolations()->count(),
            'Generated IBAN for country ' . $countryCode . ' is valid.'
        );
    }
}
