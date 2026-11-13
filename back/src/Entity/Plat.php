<?php

namespace App\Entity;

use App\Repository\PlatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlatRepository::class)]
class Plat
{
    public const TYPES = ['Entrée', 'Plat', 'Dessert'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $titre = null;

    // Entrée, Plat ou Dessert
    #[ORM\Column(length: 20)]
    private ?string $typePlat = null;

    // Nom du fichier image (stocké côté front dans /images)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    /**
     * @var Collection<int, Allergene>
     */
    #[ORM\ManyToMany(targetEntity: Allergene::class)]
    private Collection $allergenes;

    public function __construct()
    {
        $this->allergenes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getTypePlat(): ?string
    {
        return $this->typePlat;
    }

    public function setTypePlat(string $typePlat): static
    {
        $this->typePlat = $typePlat;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * @return Collection<int, Allergene>
     */
    public function getAllergenes(): Collection
    {
        return $this->allergenes;
    }

    public function addAllergene(Allergene $allergene): static
    {
        if (!$this->allergenes->contains($allergene)) {
            $this->allergenes->add($allergene);
        }

        return $this;
    }

    public function removeAllergene(Allergene $allergene): static
    {
        $this->allergenes->removeElement($allergene);

        return $this;
    }
}
