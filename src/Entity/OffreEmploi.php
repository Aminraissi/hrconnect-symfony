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

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(
        min: 5,
        max: 200,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    private ?string $description = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La localisation est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'La localisation doit contenir au moins {{ limit }} caractères',
        maxMessage: 'La localisation ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $location = null;

    // Champs ajoutés pour maintenir la compatibilité avec le code existant
    private ?bool $isActive = true;

    private ?\DateTimeInterface $datePublication = null;

    private ?string $typeContrat = null;

    private ?string $salaire = null;

    private ?string $profilRecherche = null;

    private ?string $avantages = null;

    #[ORM\OneToMany(mappedBy : 'offreEmploi', targetEntity: Candidature::class)]
    private Collection $candidatures;

    public function __construct()
    {
        $this->candidatures    = new ArrayCollection();
        $this->datePublication = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;
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
            $candidature->setOffreEmploi($this);
        }
        return $this;
    }

    public function removeCandidature(Candidature $candidature): static
    {
        if ($this->candidatures->removeElement($candidature)) {
            if ($candidature->getOffreEmploi() === $this) {
                $candidature->setOffreEmploi(null);
            }
        }
        return $this;
    }

    // Méthodes de compatibilité pour l'ancien code
    public function getTitre(): ?string
    {
        return $this->title;
    }

    public function setTitre(string $titre): static
    {
        $this->title = $titre;
        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->location;
    }

    public function setLocalisation(string $localisation): static
    {
        $this->location = $localisation;
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

    public function getTypeContrat(): ?string
    {
        return $this->typeContrat;
    }

    public function setTypeContrat(string $typeContrat): static
    {
        $this->typeContrat = $typeContrat;
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

    public function __toString(): string
    {
        return $this->title ?? '';
    }
}
