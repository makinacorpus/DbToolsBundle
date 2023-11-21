<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractEnumAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\DbToolsBundle\Helper\LoremIpsum;

/**
 * Generates lorem ipsum text.
 *
 * Available options:
 *   - paragraphs: (int) number of paragraphs, default is 1,
 *   - html: (bool) surround each paragraph with <p>, default is false.
 *   - sample_count: (int) how many different values to use (default is 100).
 */
#[AsAnonymizer(
    name: 'lorem',
    pack: 'core',
    description: <<<TXT
    Replace a text with lorem ipsum.
    Available options:
     - 'paragraphs': (int) number of paragraphs, default is 1,
     - 'html': (bool) surround each paragraph with <p>, default is false.
     - 'sample_count': (int) how many different values to use (default is 100).
    TXT
)]
class LoremIpsumAnonymizer extends AbstractEnumAnonymizer
{
    /**
     * {@inheritdoc}
     */
    protected function getSample(): array
    {
        $tag = $this->options->get('html') ? 'p' : null;
        $sampleCount = (int) $this->options->get('sample_count', 100);
        $paragraphs = (int) $this->options->get('paragraphs', 1);

        $loremIpsum = new LoremIpsum();

        $ret = [];
        for ($i = 0; $i < $sampleCount; ++$i) {
            $ret[] = $loremIpsum->paragraphs($paragraphs, $tag);
        }

        return $ret;
    }
}
