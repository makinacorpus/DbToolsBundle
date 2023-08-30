<?php

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Target;

class Column extends Target
{
  public function __construct(
    public readonly string $table,
    public readonly string $column,
  ) { }
}