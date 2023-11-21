<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\FrFR;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Query\Update;

/**
 * Anonymize french telephone numbers.
 *
 * This will create phone number with reserved prefixes for fiction and tests:
 *   - 01 99 00 XX XX
 *   - 02 61 91 XX XX
 *   - 03 53 01 XX XX
 *   - 04 65 71 XX XX
 *   - 05 36 49 XX XX
 *   - 06 39 98 XX XX
 *
 * Under the hood, it will simple send basic strings such as: 0639980000 with
 * trailing 0's randomly replaced with something else. Formating may be
 * implemented later.
 *
 * Options are:
 *   - "mode": can be "mobile" or "landline"
 */
#[AsAnonymizer(
    name: 'phone',
    pack: 'fr_fr',
    description: <<<TXT
    Anonymize with a random fictional french phone number.
    You can choose if you want a "landline" or a "mobile" phone number with option 'mode'
    TXT
)]
class PhoneNumberAnonymizer extends AbstractAnonymizer
{
    /**
     * {@inheritdoc}
     */
    public function anonymize(Update $update): void
    {
        $expr = $update->expression();

        $update->set(
            $this->columnName,
            $this->getSetIfNotNullExpression(
                $expr->concat(
                    match ($this->options->get('mode', 'mobile')) {
                        'mobile' => '063998',
                        'landline' => '026191',
                        default => throw new \InvalidArgumentException('"mode" option can be "mobile", "landline"'),
                    },
                    $this->getSqlTextPadLeftExpression(
                        $expr->randomInt(9999),
                        4,
                        '0'
                    ),
                ),
            )
        );
    }
}
