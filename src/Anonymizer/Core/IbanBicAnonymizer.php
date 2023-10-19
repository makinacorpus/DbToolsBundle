<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Common;

use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractMultipleColumnAnonymizer;
use MakinaCorpus\DbToolsBundle\Helper\Iban;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

/**
 * Anonymize an IBAN/BIC couple.
 *
 * This anonymizer handle:
 *  - "iban": A valid IBAN,
 *  - "bic": The associated to IBAN BIC number (bank identifier)
 *
 * You may specify the following two options:
 *  - "country": 2-chars country code for the IBAN to use for generation, default
 *    is to randomly choose a country for each generated IBAN, default is 500.
 *  - "sample_size": total number of different IBAN that will be generated, the higher
 *    is this number, the less duplicates you will have in the end.
 */
#[AsAnonymizer(
    name: 'iban-bic',
    pack: 'core',
    description: <<<TXT
    Anonymize IBAN and BIC on two columns.
    Map columns for each part with options ('iban' and 'bic')
    TXT
)]
class IbanBicAnonymizer extends AbstractMultipleColumnAnonymizer
{
    /**
     * @inheritdoc
     */
    protected function getColumnNames(): array
    {
        return [
            'iban',
            'bic'
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getSamples(): array
    {
        // @todo, pas d'options, pas de count ni de country, désolé.
        $ret = [];
        for ($i = 0; $i < 500; ++$i) {
            $ret[] = [
                Iban::iban('FR'),
                'BDFEFR2L', // @todo génération de bic aléatoire selon l'IBAN
            ];
        }
        return $ret;
    }
}
