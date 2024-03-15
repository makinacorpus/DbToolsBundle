<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractEnumAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\DbToolsBundle\Helper\LoremIpsum;

/**
 * Generates lorem ipsum text.
 */
#[AsAnonymizer(
    name: 'lorem',
    pack: 'core',
    description: <<<TXT
    Replace a text with some lorem ipsum.
    Default behavior is to generate a single paragraph.
    Available options:
     - 'paragraphs': (int) number of paragraphs to generate,
     - 'words': (int) number of words to generate
       (could not be used in combination with 'paragraphs' option),
     - 'html': (bool) surround each paragraph with <p>, default is false.
     - 'sample_count': (int) how many different values to use (default is 100).
    TXT
)]
class LoremIpsumAnonymizer extends AbstractEnumAnonymizer
{
    #[\Override]
    protected function getSample(): array
    {
        $tag = $this->options->get('html', false) ? 'p' : null;
        $sampleCount = (int) $this->options->get('sample_count', 100);
        $paragraphs = (int) $this->options->get('paragraphs', 1);

        if ($this->options->has('words')) {
            $words = (int) $this->options->get('words');
            if ($words <= 0) {
                throw new \InvalidArgumentException("'words' should be greater than 0.");
            }

            return $this->generateWordsSample($words,  $sampleCount);
        } else {
            $paragraphs = (int) $this->options->get('paragraphs', 1);
            if ($paragraphs <= 0) {
                throw new \InvalidArgumentException("'paragraphs' should be greater than 0.");
            }

            return $this->generateParagraphsSample($paragraphs, $sampleCount, $tag);
        }
    }

    private function generateParagraphsSample(int $paragraphs, int $sampleCount, ?string $tag): array
    {
        $loremIpsum = new LoremIpsum();

        $ret = [];
        for ($i = 0; $i < $sampleCount; ++$i) {
            $ret[] = $loremIpsum->paragraphs($paragraphs, $tag);
        }

        return $ret;
    }

    private function generateWordsSample(int $words, int $sampleCount): array
    {
        $loremIpsum = new LoremIpsum();

        $ret = [];
        for ($i = 0; $i < $sampleCount; ++$i) {
            $ret[] = $loremIpsum->words($words);
        }

        return $ret;
    }
}
