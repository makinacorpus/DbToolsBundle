<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;

class LoremIpsumAnonymizerTest extends FunctionalTestCase
{
    /** @before */
    protected function createTestData(): void
    {
        $this->createOrReplaceTable(
            'table_test',
            [
                'id' => 'integer',
                'data' => 'text',
            ],
            [
                [
                    'id' => 1,
                    'data' => 'test1',
                ],
                [
                    'id' => 2,
                    'data' => 'test2',
                ],
                [
                    'id' => 3,
                    'data' => 'test3',
                ],
                [
                    'id' => 4,
                ],
            ],
        );
    }

    public function testAnonymizeWithDefaultOptions(): void
    {
        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'data',
            'lorem',
            new Options()
        ));

        $this->assertSame(
            'test1',
            $this->getDatabaseSession()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        // Default behavior is to create on paragraph, without HTML '<p>' tag.
        $data = $datas[0];
        $this->assertNotNull($data);
        $this->assertNotSame('test1', $data);
        $this->assertStringNotContainsString('<p>', $data);
        $this->assertStringNotContainsString('</p>', $data);

        $data = $datas[1];
        $this->assertNotNull($data);
        $this->assertNotSame('test2', $data);
        $this->assertStringNotContainsString('<p>', $data);
        $this->assertStringNotContainsString('</p>', $data);

        $data = $datas[2];
        $this->assertNotNull($data);
        $this->assertNotSame('test3', $data);
        $this->assertStringNotContainsString('<p>', $data);
        $this->assertStringNotContainsString('</p>', $data);

        $this->assertNull($datas[3]);

        $this->assertGreaterThan(1, \array_unique($datas), 'All generated values are different.');
    }

    public function testAnonymizeWithParagraphsAndTag(): void
    {
        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'data',
            'lorem',
            new Options([
                'paragraphs' => 5,
                'html' => true
            ])
        ));

        $this->assertSame(
            'test1',
            $this->getDatabaseSession()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $data = $datas[0];
        $this->assertNotNull($data);
        $this->assertNotSame('test1', $data);
        $this->assertEquals(5, \substr_count($data, '<p>'));
        $this->assertEquals(5, \substr_count($data, '</p>'));

        $data = $datas[1];
        $this->assertNotNull($data);
        $this->assertEquals(5, \substr_count($data, '<p>'));
        $this->assertEquals(5, \substr_count($data, '</p>'));

        $data = $datas[2];
        $this->assertNotNull($data);
        $this->assertEquals(5, \substr_count($data, '<p>'));
        $this->assertEquals(5, \substr_count($data, '</p>'));

        $this->assertNull($datas[3]);

        $this->assertGreaterThan(1, \array_unique($datas), 'All generated values are different.');
    }

    public function testAnonymizeWithWords(): void
    {
        $anonymizator = $this->createAnonymizatorWithConfig(new AnonymizerConfig(
            'table_test',
            'data',
            'lorem',
            new Options(['words' => 5])
        ));

        $this->assertSame(
            'test1',
            $this->getDatabaseSession()->executeQuery('select data from table_test where id = 1')->fetchOne(),
        );

        $anonymizator->anonymize();

        $datas = $this->getDatabaseSession()->executeQuery('select data from table_test order by id asc')->fetchFirstColumn();

        $data = $datas[0];
        $this->assertNotNull($data);
        $this->assertNotSame('test1', $data);
        $this->assertStringNotContainsString('<p>', $data);
        $this->assertStringNotContainsString('</p>', $data);
        $this->assertEquals(5, \str_word_count($data));

        $data = $datas[1];
        $this->assertNotNull($data);
        $this->assertStringNotContainsString('<p>', $data);
        $this->assertStringNotContainsString('</p>', $data);
        $this->assertEquals(5, \str_word_count($data));

        $data = $datas[2];
        $this->assertNotNull($data);
        $this->assertStringNotContainsString('<p>', $data);
        $this->assertStringNotContainsString('</p>', $data);
        $this->assertEquals(5, \str_word_count($data));

        $this->assertNull($datas[3]);

        $this->assertGreaterThan(1, \array_unique($datas), 'All generated values are different.');
    }
}
