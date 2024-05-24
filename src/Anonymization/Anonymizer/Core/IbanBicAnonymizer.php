<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractMultipleColumnAnonymizer;
use MakinaCorpus\DbToolsBundle\Helper\Iban;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

#[AsAnonymizer(
    name: 'iban-bic',
    pack: 'core',
    description: <<<TXT
    Anonymize IBAN and BIC on two columns.
    Map columns for each part with options ('iban' and 'bic')
    You can specify the 'country' option, which must be a two-letter country
    code (default is 'FR').
    You can also specify the sample table size using the 'sample_size' option
    (default is 500). The more samples you have, the less duplicates you will
    end up with.
    Generated BIC code will be 100% random.
    TXT
)]
class IbanBicAnonymizer extends AbstractMultipleColumnAnonymizer
{
    #[\Override]
    protected function validateOptions(): void
    {
        parent::validateOptions();

        if ($this->options->has('sample_size')) {
            $value = $this->options->getInt('sample_size');
            if ($value <= 0) {
                throw new \InvalidArgumentException("'sample_size' option must be a positive integer.");
            }
        }
        if ($this->options->has('country')) {
            $value = $this->options->getString('country');
            if (!\ctype_alpha($value) || 2 !== \strlen($value)) {
                throw new \InvalidArgumentException("'country' option must be a 2-letters country code.");
            }
        }
    }

    #[\Override]
    protected function getColumnNames(): array
    {
        return [
            'iban',
            'bic'
        ];
    }

    #[\Override]
    protected function getSamples(): array
    {
        $sampleSize = $this->options->getInt('sample_size', 500);
        $countryCode = $this->options->getString('country', 'FR');

        // @todo, pas d'options, pas de count ni de country, désolé.
        $ret = [];
        for ($i = 0; $i < $sampleSize; ++$i) {
            $ret[] = [
                Iban::iban($countryCode),
                Iban::bic(),
            ];
        }
        return $ret;
    }
}
