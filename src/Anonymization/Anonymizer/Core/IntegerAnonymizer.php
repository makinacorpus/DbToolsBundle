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
    /**
     * @inheritdoc
     */
    public function anonymize(Update $update): void
    {

        if ($this->options->has('min') && $this->options->has('max')) {
            $this->anonymizeWithMinAndMax(
                $update,
                (int) $this->options->get('min'),
                (int) $this->options->get('max')
            );
        } elseif ($this->options->has('delta')) {
            $this->anonymizeWithDelta($update, (int) $this->options->get('delta'));
        } elseif ($this->options->has('percent')) {
            $this->anonymizeWithPercent($update, (int) $this->options->get('percent'));
        } else {
            throw new \InvalidArgumentException("You should provide options with this anonymizer: min and max, or delta, or percent");
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
