<?php

namespace App\Entity;

use App\Repository\SeminaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SeminaireRepository::class)]
class Seminaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "ID_Seminaire")]
    private ?int $id = null;

    #[ORM\Column(name: "Nom_Seminaire", length: 255)]
    #[Assert\NotBlank(message: "Le nom du séminaire est obligatoire.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le nom du séminaire ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $nomSeminaire = null;

    #[ORM\Column(name: "Description", type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: "La description ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\Column(name: "Date_debut", type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de début est obligatoire.")]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: "Date_fin", type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de fin est obligatoire.")]
    #[Assert\Type("\DateTimeInterface")]
    #[Assert\GreaterThanOrEqual(
        propertyPath: "dateDebut",
        message: "La date de fin doit être postérieure ou égale à la date de début."
    )]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(name: "Lieu", length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le lieu ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $lieu = null;

    #[ORM\Column(name: "Formateur", length: 255)]
    #[Assert\NotBlank(message: "Le formateur est obligatoire.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le nom du formateur ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $formateur = null;

    #[ORM\Column(name: "Cout", type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\NotBlank(message: "Le coût est obligatoire.")]
    #[Assert\Regex(
        pattern: "/^\d+(\.\d{1,2})?$/",
        message: "Le coût doit être un nombre valide avec 2 décimales maximum."
    )]
    #[Assert\PositiveOrZero(message: "Le coût ne peut pas être négatif.")]
    private ?string $cout = null;

    #[ORM\Column(name: "Type", length: 50, nullable: true)]
    #[Assert\Length(
        max: 50,
        maxMessage: "Le type ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $type = null;

    #[ORM\OneToMany(mappedBy: 'seminaire', targetEntity: ParticipationSeminaire::class)]
    private $participations;

    // GETTERS & SETTERS...

    public function getId(): ?int { return $this->id; }
    public function getNomSeminaire(): ?string { return $this->nomSeminaire; }
    public function setNomSeminaire(string $nom): static { $this->nomSeminaire = $nom; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $date): static { $this->dateDebut = $date; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $date): static { $this->dateFin = $date; return $this; }

    public function getLieu(): ?string { return $this->lieu; }
    public function setLieu(?string $lieu): static { $this->lieu = $lieu; return $this; }

    public function getFormateur(): ?string { return $this->formateur; }
    public function setFormateur(string $formateur): static { $this->formateur = $formateur; return $this; }

    public function getCout(): ?string { return $this->cout; }
    public function setCout(?string $cout): static { $this->cout = $cout; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(?string $type): static { $this->type = $type; return $this; }
}
