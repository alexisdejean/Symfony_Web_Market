<?php

namespace App\Entity;

use App\Repository\ProduitsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitsRepository::class)]
class Produits
{
    public function __construct()
    {
        $this->panierContenus = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $prix = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $couleur = null;

    #[ORM\Column(length: 255)]
    private ?string $matiere = null;

    #[ORM\Column(length: 255)]
    private ?string $forme = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column]
    private ?int $stock = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $date_insertion = null;

    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: PanierContenu::class)]
    private Collection $panierContenus;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getMatiere(): ?string
    {
        return $this->matiere;
    }

    public function setMatiere(string $matiere): static
    {
        $this->matiere = $matiere;

        return $this;
    }

    public function getForme(): ?string
    {
        return $this->forme;
    }

    public function setForme(string $forme): static
    {
        $this->forme = $forme;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getDateInsertion(): ?\DateTimeImmutable
    {
        return $this->date_insertion;
    }

    public function setDateInsertion(\DateTimeImmutable $date_insertion): static
    {
        $this->date_insertion = $date_insertion;

        return $this;
    }

    /**
     * @return Collection<int, PanierContenu>
     */
    public function getPanierContenus(): Collection
    {
        return $this->panierContenus;
    }

    public function addPanierContenu(PanierContenu $panierContenu): static
    {
        if (!$this->panierContenus->contains($panierContenu)) {
            $this->panierContenus->add($panierContenu);
            $panierContenu->setProduit($this);
        }

        return $this;
    }

    public function removePanierContenu(PanierContenu $panierContenu): static
    {
        if ($this->panierContenus->removeElement($panierContenu)) {
            if ($panierContenu->getProduit() === $this) {
                $panierContenu->setProduit(null);
            }
        }

        return $this;
    }
}
