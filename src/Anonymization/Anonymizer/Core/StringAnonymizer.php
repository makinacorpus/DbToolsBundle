<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractEnumAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

/**
 * Anonymize a string column with a random value from a custom sample.
 *
 * If you need to generate a complex sample, you should consider to
 * implement your own AbstractEnumAnonymizer.
 */
#[AsAnonymizer(
    name: 'string',
    pack: 'core',
    description: "Anonymize a column by setting a random value from a given 'sample' option."
)]
class StringAnonymizer extends AbstractEnumAnonymizer
{
    /**
     * @inheritdoc
     */
    protected function getSample(): array
    {
        if (!$this->options->has('sample')) {
            throw new \InvalidArgumentException(\sprintf(
                <<<TXT
                You should provide an 'sample' option with this anonymizer.
                Check your configuration for table "%s", column "%s"
                TXT,
                $this->tableName,
                $this->columnName,
            ));
        }

        return $this->options->get('sample');
    }
}
