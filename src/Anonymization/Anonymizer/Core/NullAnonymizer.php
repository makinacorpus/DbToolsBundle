<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractSingleColumnAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Query\Update;

#[AsAnonymizer(
    name: 'null',
    pack: 'core',
    description: 'Set to NULL'
)]
class NullAnonymizer extends AbstractSingleColumnAnonymizer
{
    #[\Override]
    public function createAnonymizeExpression(Update $update): Expression
    {
        return $update->expression()->null();
    }
}
