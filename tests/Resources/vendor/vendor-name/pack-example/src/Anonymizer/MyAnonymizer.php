<?php

declare(strict_types=1);

namespace DbToolsBundle\PackTest\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractEnumAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

/**
 * An test anonymizer.
 */
#[AsAnonymizer(
    name: 'my-anonymizer',
    pack: 'test',
    description: 'A anonymizer provided by the example pack of the DbToolsBundle'
)]
class MyAnonymizer extends AbstractEnumAnonymizer
{
    /**
     * Overwrite this argument with your sample.
     */
    #[\Override]
    protected function getSample(): array
    {
        return ['foo', 'bar', 'baz'];
    }
}
