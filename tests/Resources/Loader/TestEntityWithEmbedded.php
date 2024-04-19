<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Resources\Loader;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

#[ORM\Entity()]
#[ORM\Table(name: 'test_with_embedded')]
class TestEntityWithEmbedded
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "NONE")]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Anonymize(type:'email', options: ['domain' => 'toto.com'])]
    private ?string $email = null;

    #[ORM\Embedded(class: TestEmbeddableEntity::class)]
    private TestEmbeddableEntity $embeddableEntity;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

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
