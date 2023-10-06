<?php

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Target;

abstract class Target
{
  public function __construct(
    public readonly string $table,
  ) { }
}