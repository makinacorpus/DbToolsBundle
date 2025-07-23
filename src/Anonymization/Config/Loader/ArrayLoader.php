<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Config\Loader;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArrayLoader implements LoaderInterface
{
    public function __construct(
        private array $data,
        private string $connectionName,
        /**
         * Root directory from which the configuration was loaded. It allows
         * later file loading (for example, when sources are CSV or TXT files).
         */
        private readonly string $basePath,
    ) {}

    #[\Override]
    public function load(AnonymizationConfig $config): void
    {
        if ($this->connectionName !== $config->connectionName) {
            return;
        }

        $resolver = (new OptionsResolver())
            ->setRequired('anonymizer')
            ->setAllowedTypes('anonymizer', 'string')
            ->setDefault('options', [])
            ->setAllowedTypes('options', 'array')
        ;

        foreach ($this->getData() as $table => $tableConfigs) {
            foreach ($tableConfigs as $target => $targetConfig) {
                try {
                    $targetConfig = \is_array($targetConfig) ? $targetConfig : ['anonymizer' => $targetConfig];
                    $targetConfig = $resolver->resolve($targetConfig);
                } catch (ExceptionInterface $e) {
                    $message = $e->getMessage();
                    throw new \InvalidArgumentException(
                        <<<TXT
                        Error while validating configuration for table '{$table}', key '{$target}':
                        {$message}
                        TXT
                    );
                }

                $config->add(new AnonymizerConfig(
                    $table,
                    $target,
                    $targetConfig['anonymizer'],
                    new Options($targetConfig['options']),
                    $this->basePath,
                ));
            }
        }
    }

    /**
     * Please override.
     */
    protected function getData(): array
    {
        return $this->data;
    }
}
