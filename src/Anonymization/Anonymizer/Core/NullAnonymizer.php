<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Query\Update;

#[AsAnonymizer(
    name: 'null',
    pack: 'core',
    description: 'Set to NULL'
)]
class NullAnonymizer extends AbstractAnonymizer
{
    #[\Override]
    public function anonymize(Update $update): void
    {
        $expr = $update->expression();
        $update->set(
            $this->columnName,
            $expr->null(),
        );
    }
}
