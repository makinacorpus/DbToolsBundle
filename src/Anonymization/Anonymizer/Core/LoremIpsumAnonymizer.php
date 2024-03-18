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
    protected function validateOptions(): void
    {
        $this->options->getBool('html', false, true);
        $this->options->getInt('sample_count', 100, true);

        if ($this->options->has('words')) {
            if ($this->options->has('paragraphs')) {
                throw new \InvalidArgumentException("'paragraphs' option cannot be specified if 'words' is in use.");
            }

            $words = $this->options->getInt('words');
            if ($words <= 0) {
                throw new \InvalidArgumentException("'words' must be greater than 0.");
            }
        } else {
            $paragraphs = (int) $this->options->getInt('paragraphs', 1);
            if ($paragraphs <= 0) {
                throw new \InvalidArgumentException("'paragraphs' must be greater than 0.");
            }
        }
    }

    #[\Override]
    protected function getSample(): array
    {
        $sampleCount = $this->options->getInt('sample_count', 100);

        if ($this->options->has('words')) {
            $words = $this->options->getInt('words');
            if ($words <= 0) {
                throw new \InvalidArgumentException("'words' should be greater than 0.");
            }

            return $this->generateWordsSample($words, $sampleCount);
        } else {
            $paragraphs = $this->options->getInt('paragraphs', 1);
            if ($paragraphs <= 0) {
                throw new \InvalidArgumentException("'paragraphs' should be greater than 0.");
            }

            $tag = $this->options->getBool('html', false) ? 'p' : null;

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
