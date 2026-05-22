<?php

namespace App\Entity;

use App\Repository\VehicleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Entité représentant un véhicule du catalogue M-Motors.
 * Un véhicule peut être proposé à la vente ou à la location longue durée.
 */
#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle
{
    /**
     * Identifiant unique du véhicule.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Marque du véhicule (ex: Renault, Peugeot).
     */
    #[ORM\Column(length: 100)]
    private ?string $brand = null;

    /**
     * Modèle du véhicule (ex: Clio, 308).
     */
    #[ORM\Column(length: 100)]
    private ?string $model = null;

    /**
     * Année de mise en circulation du véhicule.
     */
    #[ORM\Column]
    private ?int $year = null;

    /**
     * Kilométrage du véhicule.
     */
    #[ORM\Column]
    private ?int $kilometrage = null;

    /**
     * Prix du véhicule en euros.
     * Pour la location, il s'agit du loyer mensuel.
     */
    #[ORM\Column]
    private ?float $price = null;

    /**
     * Type du véhicule : "sale" pour achat, "rental" pour location.
     */
    #[ORM\Column(length: 20)]
    private ?string $type = null;

    /**
     * Description détaillée du véhicule (optionnel).
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * URL de la photo principale du véhicule (optionnel).
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoUrl = null;

    /**
     * Indique si le véhicule est disponible au catalogue.
     */
    #[ORM\Column]
    private ?bool $isAvailable = null;
    /**
     * Liste des dossiers associés à ce véhicule.
     */
    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Dossier::class)]
    private Collection $dossiers;

    public function __construct()
    {
        $this->dossiers = new ArrayCollection();
    }

    public function getDossiers(): Collection
    {
        return $this->dossiers;
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;
        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;
        return $this;
    }

    public function getKilometrage(): ?int
    {
        return $this->kilometrage;
    }

    public function setKilometrage(int $kilometrage): static
    {
        $this->kilometrage = $kilometrage;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function setPhotoUrl(?string $photoUrl): static
    {
        $this->photoUrl = $photoUrl;
        return $this;
    }

    public function isAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): static
    {
        $this->isAvailable = $isAvailable;
        return $this;
    }
}