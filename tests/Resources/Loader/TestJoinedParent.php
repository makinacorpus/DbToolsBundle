<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Resources\Loader;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

#[ORM\Entity()]
#[ORM\Table(name: 'test_joined_parent')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'child' => TestJoinedChild::class,
])]
class TestJoinedParent
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "NONE")]
    #[ORM\Column]
    private int|null $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Anonymize(type:'email', options: ['domain' => 'toto.com'])]
    private string|null $email = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }
}
