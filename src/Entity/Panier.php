<?php

namespace App\Entity;

use App\Repository\PanierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PanierRepository::class)]
class Panier
{
    public function __construct()
    {
        $this->date_creation = new \DateTimeImmutable();
        $this->contenus = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'panier', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date_creation = null;

    #[ORM\OneToMany(mappedBy: 'panier', targetEntity: PanierContenu::class, cascade: ['persist', 'remove'])]
    private Collection $contenus;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTimeImmutable $date_creation): static
    {
        $this->date_creation = $date_creation;

        return $this;
    }

    /**
     * @return Collection<int, PanierContenu>
     */
    public function getContenus(): Collection
    {
        return $this->contenus;
    }

    public function addContenu(PanierContenu $contenu): static
    {
        if (!$this->contenus->contains($contenu)) {
            $this->contenus->add($contenu);
            $contenu->setPanier($this);
        }

        return $this;
    }

    public function removeContenu(PanierContenu $contenu): static
    {
        if ($this->contenus->removeElement($contenu)) {
            if ($contenu->getPanier() === $this) {
                $contenu->setPanier(null);
            }
        }

        return $this;
    }
}
