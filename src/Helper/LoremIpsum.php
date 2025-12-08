<?php

/**
 * Lorem Ipsum Generator
 *
 * PHP version 5.3+
 *
 * Licensed under The MIT License.
 * Redistribution of these files must retain the above copyright notice.
 *
 * @author    Josh Sherman <hello@joshtronic.com>
 * @copyright Copyright 2014-2022 Josh Sherman
 * @license   http://www.opensource.org/licenses/mit-license.html
 * @link      https://github.com/joshtronic/php-loremipsum
 *
 * Only changes are purely cosmetic, typings, resilience and bugfixes.
 */

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper;

class LoremIpsum
{
    /**
     * Whether or not we should be starting the string with "Lorem ipsum...".
     */
    private ?array $first = null;

    /**
     * A lorem ipsum vocabulary of sorts. Not a complete list as I'm unsure if
     * a complete list exists and if so, where to get it.
     */
    private array $words = [
        // Lorem ipsum...
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',

        // and the rest of the vocabulary
        'a', 'ac', 'accumsan', 'ad', 'aenean', 'aliquam', 'aliquet', 'ante',
        'aptent', 'arcu', 'at', 'auctor', 'augue', 'bibendum', 'blandit',
        'class', 'commodo', 'condimentum', 'congue', 'consequat', 'conubia',
        'convallis', 'cras', 'cubilia', 'curabitur', 'curae', 'cursus',
        'dapibus', 'diam', 'dictum', 'dictumst', 'dignissim', 'dis', 'donec',
        'dui', 'duis', 'efficitur', 'egestas', 'eget', 'eleifend', 'elementum',
        'enim', 'erat', 'eros', 'est', 'et', 'etiam', 'eu', 'euismod', 'ex',
        'facilisi', 'facilisis', 'fames', 'faucibus', 'felis', 'fermentum',
        'feugiat', 'finibus', 'fringilla', 'fusce', 'gravida', 'habitant',
        'habitasse', 'hac', 'hendrerit', 'himenaeos', 'iaculis', 'id',
        'imperdiet', 'in', 'inceptos', 'integer', 'interdum', 'justo',
        'lacinia', 'lacus', 'laoreet', 'lectus', 'leo', 'libero', 'ligula',
        'litora', 'lobortis', 'luctus', 'maecenas', 'magna', 'magnis',
        'malesuada', 'massa', 'mattis', 'mauris', 'maximus', 'metus', 'mi',
        'molestie', 'mollis', 'montes', 'morbi', 'mus', 'nam', 'nascetur',
        'natoque', 'nec', 'neque', 'netus', 'nibh', 'nisi', 'nisl', 'non',
        'nostra', 'nulla', 'nullam', 'nunc', 'odio', 'orci', 'ornare',
        'parturient', 'pellentesque', 'penatibus', 'per', 'pharetra',
        'phasellus', 'placerat', 'platea', 'porta', 'porttitor', 'posuere',
        'potenti', 'praesent', 'pretium', 'primis', 'proin', 'pulvinar',
        'purus', 'quam', 'quis', 'quisque', 'rhoncus', 'ridiculus', 'risus',
        'rutrum', 'sagittis', 'sapien', 'scelerisque', 'sed', 'sem', 'semper',
        'senectus', 'sociosqu', 'sodales', 'sollicitudin', 'suscipit',
        'suspendisse', 'taciti', 'tellus', 'tempor', 'tempus', 'tincidunt',
        'torquent', 'tortor', 'tristique', 'turpis', 'ullamcorper', 'ultrices',
        'ultricies', 'urna', 'ut', 'varius', 'vehicula', 'vel', 'velit',
        'venenatis', 'vestibulum', 'vitae', 'vivamus', 'viverra', 'volutpat',
        'vulputate',
    ];

    /**
     * Generates a single word of lorem ipsum.
     *
     * @param null|string|array $tags
     *   String or array of HTML tags to wrap output with.
     */
    public function word(null|string|array $tags = null): string
    {
        return $this->words(1, $tags);
    }

    /**
     * Generates an array of lorem ipsum words.
     *
     * @param int $count
     *   How many words to generate.
     * @return array<string>
     *   Generated lorem ipsum words
     */
    public function wordsArray(int $count = 1): array
    {
        $count = (int) $count;
        $words = [];
        $word_count = 0;

        // Shuffles and appends the word list to compensate for count
        // arguments that exceed the size of our vocabulary list
        while ($word_count < $count) {
            $shuffle = true;

            while ($shuffle) {
                $this->shuffle();

                // Checks that the last word of the list and the first word of
                // the list that's about to be appended are not the same
                if (!$word_count || $words[$word_count - 1] != $this->words[0]) {
                    $words = \array_merge($words, $this->words);
                    $word_count = \count($words);
                    $shuffle = false;
                }
            }
        }

        return \array_slice($words, 0, $count);
    }

    /**
     * Generates words of lorem ipsum.
     *
     * @param int $count
     *   How many words to generate.
     * @param null|string|array $tags
     *   String or array of HTML tags to wrap output with.
     * @return string
     *   Generated lorem ipsum words.
     */
    public function words($count = 1, null|string|array $tags = null): string
    {
        return $this->output($this->wordsArray($count), $tags);
    }

    /**

     * Generates a full sentence of lorem ipsum.
     *
     * @param null|string|array $tags
     *   String or array of HTML tags to wrap output with.
     * @return string
     *   Generated lorem ipsum sentence.
     */
    public function sentence(null|string|array $tags = null)
    {
        return $this->sentences(1, $tags);
    }

    /**
     * Generates an array of lorem ipsum sentences.
     *
     * @param int $count
     *   How many sentences to generate.
     * @return array<string>
     *   Generated lorem ipsum sentences.
     */
    public function sentencesArray($count = 1): array
    {
        $sentences = [];

        for ($i = 0; $i < $count; $i++) {
            $sentences[] = $this->punctuate($this->wordsArray($this->gauss(24.46, 5.08)));
        }

        return $sentences;
    }

    /**
     * Generates sentences of lorem ipsum.
     *
     * @param int $count
     *   How many sentences to generate.
     * @param null|string|array $tags
     *   String or array of HTML tags to wrap output with.
     * @return string
     *   Generated lorem ipsum sentences.
     */
    public function sentences($count = 1, null|string|array $tags = null): string
    {
        return $this->output($this->sentencesArray($count), $tags);
    }

    /**
     * Generates a full paragraph of lorem ipsum.
     *
     * @param null|string|array $tags
     *   String or array of HTML tags to wrap output with.
     * @return string
     *   Generated lorem ipsum paragraph.
     */
    public function paragraph(null|string|array $tags = null): string
    {
        return $this->paragraphs(1, $tags);
    }

    /**
     * Generates an array of lorem ipsum paragraphs.
     *
     * @param int $count
     *   How many paragraphs to generate.
     * @return array<string>
     *   Generated lorem ipsum paragraphs.
     */
    public function paragraphsArray($count = 1)
    {
        $paragraphs = [];

        for ($i = 0; $i < $count; $i++) {
            $paragraphs[] = $this->sentences($this->gauss(5.8, 1.93));
        }

        return $paragraphs;
    }

    /**
     * Generates paragraphs of lorem ipsum.
     *
     * @param int $count
     *   How many paragraphs to generate.
     * @param null|string|array $tags
     *   String or array of HTML tags to wrap output with.
     * @return string
     *   Generated lorem ipsum paragraphs.
     */
    public function paragraphs($count = 1, null|string|array $tags = null): string
    {
        return $this->output($this->paragraphsArray($count), $tags, "\n\n");
    }

    /**
     * Gaussian Distribution.
     *
     * This is some smart kid stuff. I went ahead and combined the N(0,1) logic
     * with the N(m,s) logic into this single function. Used to calculate the
     * number of words in a sentence, the number of sentences in a paragraph
     * and the distribution of commas in a sentence.
     *
     * @param float $mean
     *   Average value.
     * @param float $std_dev
     *   Standard deviation.
     * @return int
     *   Calculated distribution.
     */
    private function gauss(int|float $mean, int|float $std_dev): int
    {
        $x = \mt_rand() / \mt_getrandmax();
        $y = \mt_rand() / \mt_getrandmax();
        $z = \sqrt(-2 * \log($x)) * \cos(2 * \pi() * $y);

        return \intval($z * $std_dev + $mean);
    }

    /**
     * Shuffles the words, forcing "Lorem ipsum..." at the beginning if it is
     * the first time we are generating the text.
     */
    private function shuffle(): void
    {
        if ($this->first) {
            $this->first = \array_slice($this->words, 0, 8);
            $this->words = \array_slice($this->words, 8);

            \shuffle($this->words);

            $this->words = $this->first + $this->words;

            $this->first = null;
        } else {
            \shuffle($this->words);
        }
    }

    /**
     * Applies punctuation to a sentence. This includes a period at the end,
     * the injection of commas as well as capitalizing the first letter of the
     * first word of the sentence.
     *
     * @param array<string> $words
     *   Word array.
     * @return string
     *   Punctuated sentence.
     */
    private function punctuate(array $words): string
    {
        $word_count = \count($words);

        // Only worry about commas on sentences longer than 4 words.
        if ($word_count > 4) {
            $mean = \log($word_count, 6);
            $std_dev = $mean / 6;
            $commas = $this->gauss($mean, $std_dev);

            for ($i = 1; $i <= $commas; $i++) {
                $word = (int) \round($i * $word_count / ($commas + 1));

                if ($word < ($word_count - 1) && $word > 0) {
                    $words[$word] .= ',';
                }
            }
        }

        return \ucfirst(\implode(' ', $words) . '.');
    }

    /**
     * Does the rest of the processing of the strings. This includes wrapping
     * the strings in HTML tags, handling transformations with the ability of
     * back referencing and determining if the passed array should be converted
     * into a string or not.
     *
     * @param array<string> $strings
     *   An array of generated strings.
     * @param null|string|array $tags
     *   String or array of HTML tags to wrap output with.
     * @return array
     *   Generated lorem ipsum text.
     */
    private function outputArray(array $strings, null|string|array $tags): array
    {
        if ($tags) {
            if (!\is_array($tags)) {
                $tags = [$tags];
            } else {
                // Flips the array so we can work from the inside out
                $tags = \array_reverse($tags);
            }

            foreach ($strings as $key => $string) {
                foreach ($tags as $tag) {
                    if (\is_string($tag)) {
                        // Detects / applies back reference
                        if ($tag[0] == '<') {
                            $string = \str_replace('$1', $string, $tag);
                        } else {
                            $string = \sprintf('<%1$s>%2$s</%1$s>', $tag, $string);
                        }
                    }

                    $strings[$key] = $string;
                }
            }
        }

        return $strings;
    }

    /**
     * Does the rest of the processing of the strings. This includes wrapping
     * the strings in HTML tags, handling transformations with the ability of
     * back referencing and determining if the passed array should be converted
     * into a string or not.
     *
     * @param array<string> $strings
     *   An array of generated strings.
     * @param null|string|array $tags
     *   String or array of HTML tags to wrap output with.
     * @param string $delimiter
     *   The string to use when calling implode().
     * @return string
     *   Generated lorem ipsum text.
     */
    private function output(array $strings, null|string|array $tags, $delimiter = ' '): string
    {
        return \implode($delimiter, $this->outputArray($strings, $tags));
    }
}
