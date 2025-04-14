<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\ValiderCongeRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ValiderCongeRepository::class)]
#[ORM\Table(name: 'valider_conge')]
class ValiderConge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DemandeConge::class, inversedBy: 'validerConges')]
    #[ORM\JoinColumn(name: 'demande_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'La demande de congé est obligatoire.')]
    private ?DemandeConge $demandeConge = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Choice(choices: ['EN_ATTENTE', 'ACCEPTEE', 'REFUSEE'], message: 'Le statut doit être l’un des choix valides.')]
    private ?string $statut = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(name: 'dateValidation', type: 'date', nullable: false)]
    #[Assert\NotNull(message: 'La date de validation est obligatoire.')]
    private ?\DateTimeInterface $dateValidation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDemandeConge(): ?DemandeConge
    {
        return $this->demandeConge;
    }

    public function setDemandeConge(?DemandeConge $demandeConge): self
    {
        $this->demandeConge = $demandeConge;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(\DateTimeInterface $dateValidation): self
    {
        $this->dateValidation = $dateValidation;
        return $this;
    }
}