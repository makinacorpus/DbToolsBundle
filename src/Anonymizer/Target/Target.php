<?php

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Target;

class Target
{
  public function __construct(
    public readonly string $table,
  ) { }
}