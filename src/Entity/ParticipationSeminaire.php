<?php

namespace App\Entity;

use App\Repository\ParticipationSeminaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: "participationseminaire")]
#[ORM\Entity(repositoryClass: ParticipationSeminaireRepository::class)]
class ParticipationSeminaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "ID_Participation")]
    private ?int $id = null;

    #[ORM\Column(name: "Statut", type: Types::STRING, length: 20)]
    #[Assert\NotBlank(message: "Le statut est requis.")]
    #[Assert\Choice(choices: ['Inscrit', 'présent', 'absent', 'en attente'], message: "Le statut est invalide.")]
    private ?string $statut = 'en attente';

    #[ORM\Column(name: "Date_inscription", type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date d'inscription est obligatoire.")]
    #[Assert\Type(\DateTimeInterface::class)]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(name: "Evaluation", type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000, maxMessage: "L'\u00e9valuation ne doit pas dépasser {{ limit }} caractères.")]
    #[Assert\NotBlank(message: "Le evaluation est requis.")]
    private ?string $evaluation = null;

    #[ORM\Column(name: "Certificat", length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: "Le nom du certificat ne doit pas dépasser {{ limit }} caractères.")]
    #[Assert\NotBlank(message: "Le certificat est requis.")]
    private ?string $certificat = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(name: "ID_Seminaire", referencedColumnName: "ID_Seminaire")]
    #[Assert\NotBlank(message: "Le Seminaire est requis.")]
    #[Assert\NotNull(message: "Le séminaire est requis.")]
    private ?Seminaire $seminaire = null;

    #[ORM\Column(name: "ID_Employe", type: "integer")]
    #[Assert\NotNull(message: "L'employé est requis.")]
    private ?int $idEmploye = null;

    // Getters & Setters...
    public function getId(): ?int { return $this->id; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
    public function getDateInscription(): ?\DateTimeInterface { return $this->dateInscription; }
    public function setDateInscription(\DateTimeInterface $date): static { $this->dateInscription = $date; return $this; }
    public function getEvaluation(): ?string { return $this->evaluation; }
    public function setEvaluation(?string $eval): static { $this->evaluation = $eval; return $this; }
    public function getCertificat(): ?string { return $this->certificat; }
    public function setCertificat(?string $certificat): static { $this->certificat = $certificat; return $this; }
    public function getSeminaire(): ?Seminaire { return $this->seminaire; }
    public function setSeminaire(?Seminaire $sem): static { $this->seminaire = $sem; return $this; }
    public function getIdEmploye(): ?int { return $this->idEmploye; }
    public function setIdEmploye(int $id): static { $this->idEmploye = $id; return $this; }
}