<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Resources\Loader;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

#[ORM\Embeddable]
class TestEmbeddableEntity
{
    #[ORM\Column]
    #[Anonymize(type:'integer', options: ['min' => 0, 'max' => 65])]
    private ?int $age = null;

    #[ORM\Column]
    #[Anonymize(type:'integer', options: ['min' => 60, 'max' => 250])]
    private ?int $size = null;

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }
}
