<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Query\Update;

#[AsAnonymizer(
    name: 'integer',
    pack: 'core',
    description: <<<TXT
    Anonymize integers by:
        - randomly choosing an integer in a range delimited
          by 'min' and 'max' options
        - altering the initial value by adding it a random value picked
          in a range computed from the 'delta' or 'percent' options
    TXT
)]
class IntegerAnonymizer extends AbstractAnonymizer
{
    #[\Override]
    protected function validateOptions(): void
    {
        if ($this->options->has('min') && $this->options->has('max')) {
            if ($this->options->has('delta')) {
                throw new \InvalidArgumentException("'delta' option cannot be specified if 'min' and 'max' are in use.");
            }
            if ($this->options->has('percent')) {
                throw new \InvalidArgumentException("'percent' option cannot be specified if 'min' and 'max' are in use.");
            }

            $min = $this->options->getInt('min');
            $max = $this->options->getInt('max');
            if ($min >= $max) {
                throw new \InvalidArgumentException("'max' must be greater than 'min'.");
            }
        } elseif ($this->options->has('delta')) {
            if ($this->options->has('percent')) {
                throw new \InvalidArgumentException("'percent' option cannot be specified if 'min' and 'max' are in use.");
            }

            $delta = (float) $this->options->getInt('delta');
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
    public function anonymize(Update $update): void
    {
        if ($this->options->has('min') && $this->options->has('max')) {
            $this->anonymizeWithMinAndMax(
                $update,
                $this->options->getInt('min'),
                $this->options->getInt('max')
            );
        } elseif ($this->options->has('delta')) {
            $this->anonymizeWithDelta($update, $this->options->getInt('delta'));
        } elseif ($this->options->has('percent')) {
            $this->anonymizeWithPercent($update, $this->options->getInt('percent'));
        }
    }

    private function anonymizeWithMinAndMax(Update $update, int $max, int $min): void
    {
        $update->set(
            $this->columnName,
            $this->getSetIfNotNullExpression(
                $this->getRandomIntExpression($max, $min),
            ),
        );
    }

    private function anonymizeWithDelta(Update $update, int $delta): void
    {
        $expr = $update->expression();

        $update->set(
            $this->columnName,
            $expr->raw(
                '? + ?',
                [
                    $expr->column($this->columnName, $this->tableName),
                    $this->getRandomIntExpression($delta, -$delta)
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
                'integer'
            )
        );
    }
}
