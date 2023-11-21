<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\FrFR;

use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizator;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Tests\FunctionalTestCase;

class AddressAnonymizerTest extends FunctionalTestCase
{
    /** @before */
    protected function createTestData(): void
    {
        $this->createOrReplaceTable(
            'table_test',
            [
                'id' => 'integer',
                'my_street_address' => 'string',
                'my_secondary_address' => 'string',
                'my_postal_code' => 'string',
                'my_locality' => 'string',
                'my_region' => 'string',
                'my_country' => 'string',
            ],
            [
                [
                    'id' => '1',
                    'my_street_address' => 'Rue Aristide Briand',
                    'my_secondary_address' => 'La maison aux volets bleus',
                    'my_postal_code' => '44400',
                    'my_locality' => 'REZE',
                    'my_region' => 'Pays de loire',
                    'my_country' => 'FRANCE',
                ],
                [
                    'id' => '2',
                    'my_street_address' => 'Rue Jean Jaures',
                    'my_secondary_address' => 'Au dernier étage',
                    'my_postal_code' => '44000',
                    'my_locality' => 'Nantes',
                    'my_region' => 'Pays de loire',
                    'my_country' => 'FRANCE',
                ],
                [
                    'id' => '3',
                ],
            ],
        );
    }

    public function testAnonymize(): void
    {
        $config = new AnonymizationConfig();
        $config->add(new AnonymizerConfig(
            'table_test',
            'data',
            'fr_fr.address',
            new Options([
                'street_address' => 'my_street_address',
                'secondary_address' => 'my_secondary_address',
                'postal_code' => 'my_postal_code',
                'locality' => 'my_locality',
                'region' => 'my_region',
                'country' => 'my_country',
            ])
        ));

        $anonymizator = new Anonymizator(
            $this->getConnection(),
            new AnonymizerRegistry(),
            $config
        );

        $this->assertSame(
            "Rue Aristide Briand",
            $this->getConnection()->executeQuery('select my_street_address from table_test where id = 1')->fetchOne(),
        );

        foreach ($anonymizator->anonymize() as $message) {
        }

        $datas = $this->getConnection()->executeQuery('select * from table_test order by id asc')->fetchAllAssociative();
        $this->assertNotNull($datas[0]);
        $this->assertNotSame('Rue Aristide Briand', $datas[0]['my_street_address']);
        $this->assertNotSame('La maison aux volets bleus', $datas[0]['my_secondary_address']);
        $this->assertNotSame('44400', $datas[0]['my_postal_code']);
        $this->assertNotSame('REZE', $datas[0]['my_locality']);
        $this->assertNotSame('Pays de loire', $datas[0]['my_region']);
        $this->assertNotNull($datas[1]);
        $this->assertNotSame('Rue Jean Jaures', $datas[1]['my_street_address']);
        $this->assertNotSame('Au dernier étage', $datas[1]['my_secondary_address']);
        $this->assertNotSame('44000', $datas[1]['my_postal_code']);
        $this->assertNotSame('Nantes', $datas[1]['my_locality']);
        $this->assertNotSame('Pays de loire', $datas[1]['my_region']);
        /*
        $this->assertNull($datas[2]['my_street_address']);
        $this->assertNull($datas[2]['my_secondary_address']);
        $this->assertNull($datas[2]['my_postal_code']);
        $this->assertNull($datas[2]['my_locality']);
        $this->assertNull($datas[2]['my_region']);
         */
        $this->assertCount(3, \array_unique(\array_map(fn ($value) => \serialize($value), $datas)), 'All generated values are different.');
    }
}
