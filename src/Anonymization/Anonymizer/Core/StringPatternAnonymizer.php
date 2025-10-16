<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractEnumAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractMultipleColumnAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractSingleColumnAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Pattern\IntRangePart;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Pattern\Part;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Pattern\RawPart;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Pattern\RefPart;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Pattern\StringPattern;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\WithAnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\WithAnonymizerRegistryTrait;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\DbToolsBundle\Error\ConfigurationException;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\Query\Update;

/**
 * Creates a CONCAT(...) expression which is filled with all parts
 * of a generated string pattern, using other anonymizers.
 *
 * Delta that you will encounter in anonymizers is an information
 * that allows the user to create more than one JOIN statement for
 * the same multiple column anonymizer. Per default, it's always 0
 * and all columns from a same row will be shared for a single
 * updated line, keeping consistency.
 */
#[AsAnonymizer(
    name: 'pattern',
    pack: 'core',
    description: <<<TXT
    Use a string pattern to build a value using other anonymizers.
    It can contain:
      - [min,max]: randomly select and integer inside the given range,
      - {pack.anonymizer} randomly fetch a value from the given anonymizer,
      - {pack.anonymizer:column} randomly fetch a value from the given column in the given anonymizer.
    TXT,
)]
class StringPatternAnonymizer extends AbstractSingleColumnAnonymizer implements WithAnonymizerRegistry
{
    use WithAnonymizerRegistryTrait;

    private ?StringPattern $pattern = null;

    /**
     * Child anonymizers, populated during initialize() call in order to be
     * able to initialize them at the same time.
     *
     * It is then being used during clean() for cleanup, then dropped.
     *
     * @var array<string, AbstractAnonymizer>
     */
    private $childAnonymizers = [];

    #[\Override]
    protected function validateOptions(): void
    {
        // This will lazy create the StringPattern instance, which will parse
        // the value and raise ConfigurationException in case of an invalid
        // user input.
        $this->getPattern();
    }

    #[\Override]
    public function initialize(): void
    {
        parent::initialize();

        // Initialize all child anonymizers. We need to initialized each column
        // anonymizer only once, and give it columns we will need as options.
        $columns = [];
        foreach ($this->getPattern()->parts as $part) {
            if ($part instanceof RefPart) {
                if ($part->column) {
                    $columns[$part->id][$part->column] = $part->column;
                } elseif (!\array_key_exists($part->id, $columns)) {
                    $columns[$part->id] = [];
                }
            }
        }
        foreach ($columns as $name => $columns) {
            if (!$columns) {
                $this->getAnonymizer($name)->initialize();
            } else {
                $this->getAnonymizer($name, new Options($columns))->initialize();
            }
        }
    }

    #[\Override]
    public function createAnonymizeExpression(Update $update): Expression
    {
        $expr = $update->expression();

        $expressions = [];
        foreach ($this->getPattern()->parts as $part) {
            \assert($part instanceof Part);

            if ($part instanceof IntRangePart) {
                // Integer anonymizer doesn't require any initialization so we
                // can safely do this, without any prior initialization. For
                // each range we have in the pattern, options will be different
                // so we need to create as many instances as we have ranges.
                $childAnonymizer = $this->getAnonymizer(
                    'integer',
                    new Options([
                        'min' => $part->start,
                        'max' => $part->stop,
                    ]),
                );
                \assert($childAnonymizer instanceof AbstractSingleColumnAnonymizer);

                $expressions[] = $childAnonymizer->createAnonymizeExpression($update);

                continue;
            }

            if ($part instanceof RawPart) {
                $expressions[] = $expr->value($part->string, 'text');

                continue;
            }

            if ($part instanceof RefPart) {
                // Anonymizer was prepolulated with options during initialize()
                // which makes passing any options useless (the cached instance
                // will be retrieved, already carries options).
                $childAnonymizer = $this->getAnonymizer($part->id, null, $part->delta);

                if ($part->column) {
                    if (!$childAnonymizer instanceof AbstractMultipleColumnAnonymizer) {
                        throw new ConfigurationException(\sprintf("Anonymizer '%s' is not a single value anonymizer.", $part->id));
                    }

                    $joinAlias = $childAnonymizer->addJoinToQuery($update);
                    $expressions[] = $expr->column($part->column, $joinAlias);

                    continue;
                }

                if ($childAnonymizer instanceof AbstractSingleColumnAnonymizer) {
                    $expressions[] = $childAnonymizer->createAnonymizeExpression($update);

                    continue;
                }
            }
        }

        return $this->getSetIfNotNullExpression(
            $expr->concat(...$expressions),
        );
    }

    #[\Override]
    public function clean(): void
    {
        try {
            // Clean all child anonymizers.
            foreach ($this->childAnonymizers as $anonymizer) {
                \assert($anonymizer instanceof AbstractAnonymizer);
                $anonymizer->clean();
            }
            $this->childAnonymizers = [];
        } finally {
            parent::clean();
        }
    }

    /**
     * Get string pattern.
     */
    private function getPattern(): StringPattern
    {
        return $this->pattern ??= new StringPattern($this->options->getString('pattern', null, true));
    }

    /**
     * Create child anonymizer.
     */
    private function getAnonymizer(string $anonymizer, ?Options $options = null, int $delta = 0): AbstractAnonymizer
    {
        $key = \sprintf("%s[%d]", $anonymizer, $delta);

        if ($ret = $this->childAnonymizers[$key] ?? null) {
            return $ret;
        }

        $config = new AnonymizerConfig($this->tableName, $this->columnName, $anonymizer, $options ?? new Options());

        return $this->childAnonymizers[$key] = $this
            ->getAnonymizerRegistry()
            ->createAnonymizer(
                $anonymizer,
                $config,
                $this->context,
                $this->databaseSession
            )
        ;
    }
}
