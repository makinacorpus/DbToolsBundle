<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Resources\Loader;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'test_with_embedded')]
class TestEntityWithEmbedded
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\Embedded(class: TestEmbeddableEntity::class)]
    private TestEmbeddableEntity $embeddableEntity;



    public function getEmbeddableEntity(): ?TestEmbeddableEntity
    {
        return $this->embeddableEntity;
    }

    public function setEmbeddableEntity(TestEmbeddableEntity $embeddableEntity): static
    {
        $this->embeddableEntity = $embeddableEntity;

        return $this;
    }
}
