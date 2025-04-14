<?php
namespace App\Entity;

use App\Repository\OffreEmploiRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OffreEmploiRepository::class)]
class OffreEmploi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $titre = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le type de contrat est obligatoire')]
    #[Assert\Choice(
        choices: ['CDI', 'CDD', 'Stage', 'Alternance', 'Freelance'],
        message: 'Choisissez un type de contrat valide'
    )]
    private ?string $typeContrat = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La localisation est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'La localisation doit contenir au moins {{ limit }} caractères',
        maxMessage: 'La localisation ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $localisation = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le salaire est obligatoire')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le salaire ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $salaire = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(
        min: 50,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le profil recherché est obligatoire')]
    #[Assert\Length(
        min: 50,
        minMessage: 'Le profil recherché doit contenir au moins {{ limit }} caractères'
    )]
    private ?string $profilRecherche = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        min: 10,
        minMessage: 'Les avantages doivent contenir au moins {{ limit }} caractères'
    )]
    private ?string $avantages = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'La date de publication est obligatoire')]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeInterface $datePublication = null;

    #[ORM\OneToMany(mappedBy : 'offre', targetEntity: Candidature::class)]
    private Collection $candidatures;

    public function __construct()
    {
        $this->candidatures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getTypeContrat(): ?string
    {
        return $this->typeContrat;
    }

    public function setTypeContrat(string $typeContrat): static
    {
        $this->typeContrat = $typeContrat;
        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(string $localisation): static
    {
        $this->localisation = $localisation;
        return $this;
    }

    public function getSalaire(): ?string
    {
        return $this->salaire;
    }

    public function setSalaire(string $salaire): static
    {
        $this->salaire = $salaire;
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

    public function getProfilRecherche(): ?string
    {
        return $this->profilRecherche;
    }

    public function setProfilRecherche(string $profilRecherche): static
    {
        $this->profilRecherche = $profilRecherche;
        return $this;
    }

    public function getAvantages(): ?string
    {
        return $this->avantages;
    }

    public function setAvantages(?string $avantages): static
    {
        $this->avantages = $avantages;
        return $this;
    }

    public function isIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->datePublication;
    }

    public function setDatePublication(\DateTimeInterface $datePublication): static
    {
        $this->datePublication = $datePublication;
        return $this;
    }

    /**
     * @return Collection<int, Candidature>
     */
    public function getCandidatures(): Collection
    {
        return $this->candidatures;
    }

    public function addCandidature(Candidature $candidature): static
    {
        if (! $this->candidatures->contains($candidature)) {
            $this->candidatures->add($candidature);
            $candidature->setOffre($this);
        }
        return $this;
    }

    public function removeCandidature(Candidature $candidature): static
    {
        if ($this->candidatures->removeElement($candidature)) {
            if ($candidature->getOffre() === $this) {
                $candidature->setOffre(null);
            }
        }
        return $this;
    }
}
