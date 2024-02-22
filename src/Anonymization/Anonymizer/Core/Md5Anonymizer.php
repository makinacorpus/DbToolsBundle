<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Query\Update;

#[AsAnonymizer(
    name: 'md5',
    pack: 'core',
    description: <<<TXT
    Anonymize a column by hashing its value.
    Options are 'use_salt' (default: true).
    Using a salt prevents prevents reversing the hash of values using rainbow tables.
    TXT
)]
class Md5Anonymizer extends AbstractAnonymizer
{
    /**
     * @inheritdoc
     */
    public function anonymize(Update $update): void
    {
        $expr = $update->expression();
        $columnExpr = $expr->column($this->columnName, $this->tableName);

        if ($this->options->get('use_salt', true)) {
            $columnExpr = $expr->concat($columnExpr, $expr->value($this->getSalt()));

            $update->set(
                $this->columnName,
                // Work around some RDBMS not seeing the NULL value anymore
                // once we added the string concat.
                $this->getSetIfNotNullExpression(
                    $expr->md5($columnExpr)
                ),
            );
        } else {
            $update->set($this->columnName, $expr->md5($columnExpr));
        }
    }
}
