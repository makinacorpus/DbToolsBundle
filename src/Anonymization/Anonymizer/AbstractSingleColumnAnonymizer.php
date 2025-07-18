<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer;

use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Query\Update;

/**
 * Anonymizer that targets a single column.
 *
 * Using this as a base class will allow more complex anonymizers to re-use
 * the generated SQL code of this anonymizer in more complex SQL expressions.
 */
abstract class AbstractSingleColumnAnonymizer extends AbstractAnonymizer
{
    #[\Override]
    final public function anonymize(Update $update): void
    {
        $update->set($this->columnName, $this->createAnonymizeExpression($update));
    }

    /**
     * Create anonymization SQL expression, the expression must return a value, even if null.
     */
    abstract public function createAnonymizeExpression(Update $update): Expression;
}
