<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Datasource;

abstract class MultipleColumnDatasource extends Datasource
{
    private array $columns = [];

    public function __construct(
        string $name,
        /** @var array<string> */
        array $columns,
    ) {
        parent::__construct($name);

        if ($columns) {
            foreach (\array_values($columns) as $index => $column) {
                if (!\is_string($column)) {
                    $this->throwError(\sprintf("column %d is not a string", $index));
                }
                $this->columns[$index] = $column;
            }
        } else {
            $this->throwError("columns cannot be empty");
        }
    }
}
