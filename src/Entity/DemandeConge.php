<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\DemandeCongeRepository;

#[ORM\Entity(repositoryClass: DemandeCongeRepository::class)]
#[ORM\Table(name: 'demande_conge')]
class DemandeConge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Employe::class, inversedBy: 'demandeConges')]
    #[ORM\JoinColumn(name: 'employe_id', referencedColumnName: 'id')]
    private ?Employe $employe = null;

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): self
    {
        $this->employe = $employe;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $typeConge = null;

    public function getTypeConge(): ?string
    {
        return $this->typeConge;
    }

    public function setTypeConge(string $typeConge): self
    {
        $this->typeConge = $typeConge;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $dateDebut = null;

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $dateFin = null;

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ValiderConge::class, mappedBy: 'demandeConge')]
    private Collection $validerConges;

    public function __construct()
    {
        $this->validerConges = new ArrayCollection();
    }

    /**
     * @return Collection<int, ValiderConge>
     */
    public function getValiderConges(): Collection
    {
        if (!$this->validerConges instanceof Collection) {
            $this->validerConges = new ArrayCollection();
        }
        return $this->validerConges;
    }

    public function addValiderConge(ValiderConge $validerConge): self
    {
        if (!$this->getValiderConges()->contains($validerConge)) {
            $this->getValiderConges()->add($validerConge);
        }
        return $this;
    }

    public function removeValiderConge(ValiderConge $validerConge): self
    {
        $this->getValiderConges()->removeElement($validerConge);
        return $this;
    }

}
