<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $numeroCommande = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateCommande = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $datePrestation = null;

    #[ORM\Column(length: 50)]
    private ?string $heureLivraison = null;

    #[ORM\Column]
    private ?float $prixMenu = null;

    #[ORM\Column]
    private ?int $nombrePersonne = null;

    #[ORM\Column]
    private ?float $prixLivraison = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column]
    private ?bool $pretMateriel = null;

    #[ORM\Column]
    private ?bool $restitionMateriel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresseLivraison = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieuPrestation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motifAnnulation = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $modeContact = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Menu $menu = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroCommande(): ?string
    {
        return $this->numeroCommande;
    }

    public function setNumeroCommande(string $numeroCommande): static
    {
        $this->numeroCommande = $numeroCommande;

        return $this;
    }

    public function getDateCommande(): ?\DateTime
    {
        return $this->dateCommande;
    }

    public function setDateCommande(\DateTime $dateCommande): static
    {
        $this->dateCommande = $dateCommande;

        return $this;
    }

    public function getDatePrestation(): ?\DateTime
    {
        return $this->datePrestation;
    }

    public function setDatePrestation(\DateTime $datePrestation): static
    {
        $this->datePrestation = $datePrestation;

        return $this;
    }

    public function getHeureLivraison(): ?string
    {
        return $this->heureLivraison;
    }

    public function setHeureLivraison(string $heureLivraison): static
    {
        $this->heureLivraison = $heureLivraison;

        return $this;
    }

    public function getPrixMenu(): ?float
    {
        return $this->prixMenu;
    }

    public function setPrixMenu(float $prixMenu): static
    {
        $this->prixMenu = $prixMenu;

        return $this;
    }

    public function getNombrePersonne(): ?int
    {
        return $this->nombrePersonne;
    }

    public function setNombrePersonne(int $nombrePersonne): static
    {
        $this->nombrePersonne = $nombrePersonne;

        return $this;
    }

    public function getPrixLivraison(): ?float
    {
        return $this->prixLivraison;
    }

    public function setPrixLivraison(float $prixLivraison): static
    {
        $this->prixLivraison = $prixLivraison;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function isPretMateriel(): ?bool
    {
        return $this->pretMateriel;
    }

    public function setPretMateriel(bool $pretMateriel): static
    {
        $this->pretMateriel = $pretMateriel;

        return $this;
    }

    public function isRestitionMateriel(): ?bool
    {
        return $this->restitionMateriel;
    }

    public function setRestitionMateriel(bool $restitionMateriel): static
    {
        $this->restitionMateriel = $restitionMateriel;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getMenu(): ?Menu
    {
        return $this->menu;
    }

    public function setMenu(?Menu $menu): static
    {
        $this->menu = $menu;

        return $this;
    }

    public function getAdresseLivraison(): ?string
    {
        return $this->adresseLivraison;
    }

    public function setAdresseLivraison(?string $adresseLivraison): static
    {
        $this->adresseLivraison = $adresseLivraison;

        return $this;
    }

    public function getLieuPrestation(): ?string
    {
        return $this->lieuPrestation;
    }

    public function setLieuPrestation(?string $lieuPrestation): static
    {
        $this->lieuPrestation = $lieuPrestation;

        return $this;
    }

    public function getMotifAnnulation(): ?string
    {
        return $this->motifAnnulation;
    }

    public function setMotifAnnulation(?string $motifAnnulation): static
    {
        $this->motifAnnulation = $motifAnnulation;

        return $this;
    }

    public function getModeContact(): ?string
    {
        return $this->modeContact;
    }

    public function setModeContact(?string $modeContact): static
    {
        $this->modeContact = $modeContact;

        return $this;
    }
}
