<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Resources\Loader;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

#[ORM\Entity()]
#[ORM\Table(name: 'test_joined_child')]
class TestJoinedChild extends TestJoinedParent
{
    #[Anonymize(type: 'constant', options: ['value' => 'https://fr.wikipedia.org/wiki/Wikip%C3%A9dia:Accueil_principal#/media/Fichier:Myotis_crypticus_-_Manuel_Ruedi.jpg'])]
    #[ORM\Column(length: 255, nullable: true)]
    private string|null $url = null;

    #[Anonymize(type: 'constant', options: ['value' => 'https://fr.wikipedia.org/wiki/Wikip%C3%A9dia:Accueil_principal#/media/Fichier:Myotis_crypticus_-_Manuel_Ruedi.jpg'])]
    #[ORM\Column(length: 255, nullable: true)]
    private string|null $thumbnail_url = null;

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnail_url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function setThumbnailUrl(?string $thumbnailUrl): static
    {
        $this->thumbnail_url = $thumbnailUrl;

        return $this;
    }
}
