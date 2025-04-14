<?php

namespace App\Entity;

use App\Repository\CandidatureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
class Candidature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'candidatures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Candidat $candidat = null;

    #[ORM\ManyToOne(inversedBy: 'candidatures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?OffreEmploi $offreEmploi = null;

    #[ORM\Column(length: 255)]
    private ?string $cv = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'En cours';

    public function __construct()
    {
        // Générer une référence unique
        $this->reference = 'CAN' . rand(10000, 99999);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCandidat(): ?Candidat
    {
        return $this->candidat;
    }

    public function setCandidat(?Candidat $candidat): static
    {
        $this->candidat = $candidat;
        return $this;
    }

    public function getOffreEmploi(): ?OffreEmploi
    {
        return $this->offreEmploi;
    }

    public function setOffreEmploi(?OffreEmploi $offreEmploi): static
    {
        $this->offreEmploi = $offreEmploi;
        return $this;
    }

    public function getCv(): ?string
    {
        return $this->cv;
    }

    public function setCv(string $cv): static
    {
        $this->cv = $cv;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    // Méthodes de compatibilité pour l'ancien code
    public function getOffre(): ?OffreEmploi
    {
        return $this->offreEmploi;
    }

    public function setOffre(?OffreEmploi $offre): static
    {
        $this->offreEmploi = $offre;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->status;
    }

    public function setStatut(string $statut): static
    {
        $this->status = $statut;
        return $this;
    }
}