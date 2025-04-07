<?php
namespace App\Entity;

use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
#[ORM\Table(name: 'formations')]
class Formation
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

    #[ORM\ManyToOne(targetEntity: Formateur::class, inversedBy: 'formations')]
    #[ORM\JoinColumn(name: 'formateur_id', referencedColumnName: 'id')]
    private ?Formateur $formateur = null;

    public function getFormateur(): ?Formateur
    {
        return $this->formateur;
    }

    public function setFormateur(?Formateur $formateur): self
    {
        $this->formateur = $formateur;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'The title is required.')]
    #[Assert\Length(max: 255, maxMessage: 'The title must not exceed 255 characters.')]
    private ?string $title = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $image = null;

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank(message: 'The description is required.')]
    #[Assert\Length(max: 255, maxMessage: 'The description must not exceed 255 characters.')]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false)]
    private ?bool $is_online = null;

    public function is_online(): ?bool
    {
        return $this->is_online;
    }

    public function setIs_online(bool $is_online): self
    {
        $this->is_online = $is_online;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $place = null;

    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function setPlace(?string $place): self
    {
        $this->place = $place;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    #[Assert\NotNull(message: 'Latitude is required.')]
    #[Assert\Type(type: 'float', message: 'Latitude must be a decimal number.')]
    private ?float $lat = null;

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function setLat(?float $lat): self
    {
        $this->lat = $lat;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    #[Assert\NotNull(message: 'Longitude is required.')]
    #[Assert\Type(type: 'float', message: 'Longitude must be a decimal number.')]
    private ?float $lng = null;

    public function getLng(): ?float
    {
        return $this->lng;
    }

    public function setLng(?float $lng): self
    {
        $this->lng = $lng;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false)]
    private ?bool $available_for_employee = null;

    public function isAvailable_for_employee(): ?bool
    {
        return $this->available_for_employee;
    }

    public function setAvailable_for_employee(bool $available_for_employee): self
    {
        $this->available_for_employee = $available_for_employee;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false)]
    private ?bool $available_for_intern = null;

    public function isAvailable_for_intern(): ?bool
    {
        return $this->available_for_intern;
    }

    public function setAvailable_for_intern(bool $available_for_intern): self
    {
        $this->available_for_intern = $available_for_intern;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Assert\NotNull(message: 'Start date is required.')]
    #[Assert\Type(\DateTimeInterface::class, message: 'Start date must be a valid datetime.')]
    private ?\DateTimeInterface $start_date = null;

    public function getStart_date(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStart_date(\DateTimeInterface $start_date) : self
    {
        $this->start_date = $start_date;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Assert\NotNull(message: 'End date is required.')]
    #[Assert\Type(\DateTimeInterface::class, message: 'End date must be a valid datetime.')]
    #[Assert\Expression(
        "this.getEndDate() >= this.getStartDate()",
        message: "End date must be after start date."
    )]
    private ?\DateTimeInterface $end_date = null;

    public function getEnd_date(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function setEnd_date( ? \DateTimeInterface $end_date) : self
    {
        $this->end_date = $end_date;
        return $this;
    }

    #[ORM\Column(type : 'decimal', nullable: true)]
    #[Assert\NotNull(message: 'Price is required.')]
    #[Assert\Type(type: 'float', message: 'Price must be a decimal number.')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Price must be zero or positive.')]
    private ?float $price = null;

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Quiz::class, mappedBy: 'formation')]
    private Collection $quizs;

    /**
     * @return Collection<int, Quiz>
     */
    public function getQuizs(): Collection
    {
        if (! $this->quizs instanceof Collection) {
            $this->quizs = new ArrayCollection();
        }
        return $this->quizs;
    }

    public function addQuiz(Quiz $quiz): self
    {
        if (! $this->getQuizs()->contains($quiz)) {
            $this->getQuizs()->add($quiz);
        }
        return $this;
    }

    public function removeQuiz(Quiz $quiz): self
    {
        $this->getQuizs()->removeElement($quiz);
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Employe::class, inversedBy: 'formations')]
    #[ORM\JoinTable(
        name: 'formation_participation',
        joinColumns: [
            new ORM\JoinColumn(name: 'formation_id', referencedColumnName: 'id'),
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'employe_id', referencedColumnName: 'id'),
        ]
    )]
    private Collection $employes;

    public function __construct()
    {
        $this->quizs    = new ArrayCollection();
        $this->employes = new ArrayCollection();
    }

    /**
     * @return Collection<int, Employe>
     */
    public function getEmployes(): Collection
    {
        if (! $this->employes instanceof Collection) {
            $this->employes = new ArrayCollection();
        }
        return $this->employes;
    }

    public function addEmploye(Employe $employe): self
    {
        if (! $this->getEmployes()->contains($employe)) {
            $this->getEmployes()->add($employe);
        }
        return $this;
    }

    public function removeEmploye(Employe $employe): self
    {
        $this->getEmployes()->removeElement($employe);
        return $this;
    }

    public function isOnline(): ?bool
    {
        return $this->is_online;
    }

    public function setIsOnline(bool $is_online): static
    {
        $this->is_online = $is_online;

        return $this;
    }

    public function isAvailableForEmployee(): ?bool
    {
        return $this->available_for_employee;
    }

    public function setAvailableForEmployee(bool $available_for_employee): static
    {
        $this->available_for_employee = $available_for_employee;

        return $this;
    }

    public function isAvailableForIntern(): ?bool
    {
        return $this->available_for_intern;
    }

    public function setAvailableForIntern(bool $available_for_intern): static
    {
        $this->available_for_intern = $available_for_intern;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStartDate( ? \DateTimeInterface $start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function setEndDate( ? \DateTimeInterface $end_date): static
    {
        $this->end_date = $end_date;

        return $this;
    }

}
