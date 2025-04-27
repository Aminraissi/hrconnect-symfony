<?php

namespace App\Entity;

use App\Repository\DemandeCongeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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
    #[Assert\NotNull(message: "Employee is required")]
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

    #[ORM\Column(name: 'typeConge', type: 'string', length: 50, nullable: false)]
    #[Assert\NotBlank(message: "Leave type is required")]
    #[Assert\Length(
        max: 50,
        maxMessage: "Leave type must not exceed {{ limit }} characters"
    )]
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

    #[ORM\Column(name: 'dateDebut', type: 'date', nullable: false)]
    #[Assert\NotNull(message: "Start date is required")]
    #[Assert\Type(\DateTimeInterface::class, message: "Start date must be a valid date")]
    #[Assert\GreaterThanOrEqual(
        "today",
        message: "Start date must be today or in the future"
    )]
    private ?\DateTimeInterface $dateDebut = null;

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    #[ORM\Column(name: 'dateFin', type: 'date', nullable: false)]
    #[Assert\NotNull(message: "End date is required")]
    #[Assert\Type(\DateTimeInterface::class, message: "End date must be a valid date")]
    #[Assert\Expression(
        "this.getDateFin() >= this.getDateDebut()",
        message: "End date must be after start date"
    )]
    private ?\DateTimeInterface $dateFin = null;

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 20, nullable: false)]
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ValiderConge::class, mappedBy: 'demandeConge', cascade: ['remove'])]
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
        return $this->validerConges;
    }

    public function addValiderConge(ValiderConge $validerConge): self
    {
        if (!$this->validerConges->contains($validerConge)) {
            $this->validerConges[] = $validerConge;
            $validerConge->setDemandeConge($this);
        }

        return $this;
    }

    public function removeValiderConge(ValiderConge $validerConge): self
    {
        if ($this->validerConges->removeElement($validerConge)) {
            // set the owning side to null (unless already changed)
            if ($validerConge->getDemandeConge() === $this) {
                $validerConge->setDemandeConge(null);
            }
        }

        return $this;
    }
}