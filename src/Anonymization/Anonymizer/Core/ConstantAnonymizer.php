<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractSingleColumnAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Expression;
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
class ConstantAnonymizer extends AbstractSingleColumnAnonymizer
{
    #[\Override]
    protected function validateOptions(): void
    {
        $this->options->get('value', null, true);
    }

    #[\Override]
    public function createAnonymizeExpression(Update $update): Expression
    {
        $expr = $update->expression();

        return $this->getSetIfNotNullExpression(
            $expr->cast(
                $this->options->get('value'),
                $this->options->get('type', 'text')
            )
        );
    }
}
