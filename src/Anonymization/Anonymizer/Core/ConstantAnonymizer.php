<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Query\Update;

#[AsAnonymizer(
    name: 'constant',
    pack: 'core',
    description: <<<TXT
    Set all value to a constant value.
    Options are:
        - `value`: the value you want to use to fill the column
        - `type`: a SQL type for the given value (default is 'text')
    TXT
)]
class ConstantAnonymizer extends AbstractAnonymizer
{
    #[\Override]
    protected function validateOptions(): void
    {
        $this->options->get('value', null, true);
    }

    #[\Override]
    public function anonymize(Update $update): void
    {
        $expr = $update->expression();

        $update->set(
            $this->columnName,
            $this->getSetIfNotNullExpression(
                $expr->cast(
                    $this->options->get('value'),
                    $this->options->get('type', 'text')
                )
            ),
        );
    }
}
