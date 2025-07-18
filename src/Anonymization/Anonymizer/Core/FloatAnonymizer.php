<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractSingleColumnAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Expression;
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
class FloatAnonymizer extends AbstractSingleColumnAnonymizer
{
    #[\Override]
    protected function validateOptions(): void
    {
        $precision = $this->options->getInt('precision', 2);
        if ($precision <= 0) {
            throw new \InvalidArgumentException("'precision' must be greater than 0.");
        }

        if ($this->options->has('min') && $this->options->has('max')) {
            if ($this->options->has('delta')) {
                throw new \InvalidArgumentException("'delta' option cannot be specified if 'min' and 'max' are in use.");
            }
            if ($this->options->has('percent')) {
                throw new \InvalidArgumentException("'percent' option cannot be specified if 'min' and 'max' are in use.");
            }

            $min = $this->options->getFloat('min');
            $max = $this->options->getFloat('max');
            if ($min >= $max) {
                throw new \InvalidArgumentException("'max' must be greater than 'min'.");
            }
        } elseif ($this->options->has('delta')) {
            if ($this->options->has('percent')) {
                throw new \InvalidArgumentException("'percent' option cannot be specified if 'min' and 'max' are in use.");
            }

            $delta = (float) $this->options->getFloat('delta');
            if ($delta <= 0) {
                throw new \InvalidArgumentException("'delta' must be greater than 0.");
            }
        } elseif ($this->options->has('percent')) {
            $percent = (int) $this->options->getInt('percent');
            if ($percent <= 0) {
                throw new \InvalidArgumentException("'percent' must be greater than 0.");
            }
        } else {
            throw new \InvalidArgumentException("You must provide options with this anonymizer: both min and max, or either delta or percent.");
        }
    }

    #[\Override]
    public function createAnonymizeExpression(Update $update): Expression
    {
        $precision = $this->options->getInt('precision', 2);
        $precision = 10 ** $precision;

        if ($this->options->has('min') && $this->options->has('max')) {
            return $this->anonymizeWithMinAndMax(
                $update,
                $precision,
                $this->options->getFloat('min'),
                $this->options->getFloat('max')
            );
        } elseif ($this->options->has('delta')) {
            return $this->anonymizeWithDelta(
                $update,
                $precision,
                $this->options->getFloat('delta')
            );
        } elseif ($this->options->has('percent')) {
            return $this->anonymizeWithPercent($update, $this->options->getInt('percent'));
        }
        throw new \Exception("Unreacheable code.");
    }

    private function anonymizeWithMinAndMax(Update $update, int $precision, float $max, float $min): Expression
    {
        $randomInt = $this->getRandomIntExpression(
            (int) \floor($max * $precision),
            (int) \ceil($min * $precision)
        );

        $expr = $update->expression();

        return $this->getSetIfNotNullExpression(
            $expr->raw(
                '? / ?',
                [
                    $expr->cast($randomInt, 'float'),
                    $expr->cast($precision, 'float'),
                ]
            )
        );
    }

    private function anonymizeWithDelta(Update $update, int $precision, float $delta): Expression
    {
        $randomInt = $this->getRandomIntExpression(
            (int) \floor(-$delta * $precision),
            (int) \ceil($delta * $precision)
        );

        $expr = $update->expression();

        return $expr->raw(
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
        );
    }

    private function anonymizeWithPercent(Update $update, int $percent): Expression
    {
        $expr = $update->expression();

        return $expr->cast(
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
        );
    }
}
