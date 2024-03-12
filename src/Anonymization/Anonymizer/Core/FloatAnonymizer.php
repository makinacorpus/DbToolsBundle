<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Query\Update;

#[AsAnonymizer(
    name: 'float',
    pack: 'core',
    description: <<<TXT
    Anonymize with a random float between two bounds.
    Options are 'min' , 'max' and 'precision' (default 2).
    TXT
)]
class FloatAnonymizer extends AbstractAnonymizer
{
    /**
     * @inheritdoc
     */
    public function anonymize(Update $update): void
    {
        if (!($this->options->has('min') && $this->options->has('max'))) {
            throw new \InvalidArgumentException("You should provide 2 options (min and max) with this anonymizer");
        }

        $max = $this->options->get('max');
        $min = $this->options->get('min');
        $precision = 10 ** $this->options->get('precision', 2);

        $randomInt = $this->getRandomIntExpression(
            (int) \floor($max * $precision),
            (int) \ceil($min * $precision)
        );

        $expr = $update->expression();

        $update->set(
            $this->columnName,
            $this->getSetIfNotNullExpression(
                $expr->raw(
                    '? / ?',
                    [
                        $expr->cast($randomInt, 'float'),
                        $expr->cast($precision, 'float'),
                    ]
                )
            ),
        );
    }
}
