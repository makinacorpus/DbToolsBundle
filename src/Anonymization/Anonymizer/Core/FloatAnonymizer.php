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
    Anonymize float by:
        - randomly choosing an integer in a range delimited
          by 'min' and 'max' options
        - altering the initial value by adding it a random value picked
          in a range computed from the 'delta' or 'percent' options
    You may also specify a 'precision' (default 2).
    TXT
)]
class FloatAnonymizer extends AbstractAnonymizer
{
    /**
     * @inheritdoc
     */
    public function anonymize(Update $update): void
    {

        $precision = $this->options->get('precision', 2);
        if ($precision <= 0) {
            throw new \InvalidArgumentException("'precision' should be greater than 0.");
        }
        $precision = 10 ** $precision;

        if ($this->options->has('min') && $this->options->has('max')) {
            $min = (float) $this->options->get('min');
            $max = (float) $this->options->get('max');
            if ($min >= $max) {
                throw new \InvalidArgumentException("'max' should be greater than 'min'.");
            }

            $this->anonymizeWithMinAndMax($update, $precision, $min, $max);
        } elseif ($this->options->has('delta')) {
            $delta = (float) $this->options->get('delta');
            if ($delta <= 0) {
                throw new \InvalidArgumentException("'delta' should be greater than 0.");
            }

            $this->anonymizeWithDelta($update,  $precision, $delta);
        } elseif ($this->options->has('percent')) {
            $percent = (int) $this->options->get('percent');
            if ($percent <= 0) {
                throw new \InvalidArgumentException("'percent' should be greater than 0.");
            }

            $this->anonymizeWithPercent($update, $percent);
        } else {
            throw new \InvalidArgumentException("You should provide options with this anonymizer: both min and max, or either delta or percent.");
        }
    }

    private function anonymizeWithMinAndMax(Update $update, int $precision, float $max, float $min): void
    {
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

    private function anonymizeWithDelta(Update $update, int $precision, float $delta): void
    {
        $randomInt = $this->getRandomIntExpression(
            (int) \floor(-$delta * $precision),
            (int) \ceil($delta * $precision)
        );

        $expr = $update->expression();

        $update->set(
            $this->columnName,
            $expr->raw(
                '? + ?',
                [
                    $expr->column($this->columnName, $this->tableName),
                    $expr->raw(
                        '? / ?',
                        [
                            $expr->cast($randomInt, 'float'),
                            $expr->cast($precision, 'float'),
                        ]
                    )
                ]
            )
        );
    }

    private function anonymizeWithPercent(Update $update, int $percent): void
    {
        $expr = $update->expression();

        $update->set(
            $this->columnName,
            $expr->cast(
                $expr->raw(
                    '? * (?) / 100',
                    [
                        $expr->column($this->columnName, $this->tableName),
                        $this->getRandomIntExpression(
                            100 + $percent,
                            100 - $percent,
                        )
                    ]
                ),
                'float'
            )
        );
    }
}
