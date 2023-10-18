<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

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
#[AsAnonymizer('lorem')]
class LoremIpsumAnonymizer extends EnumAnonymizer
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
