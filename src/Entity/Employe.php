<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\EmployeRepository;

#[ORM\Entity(repositoryClass: EmployeRepository::class)]
#[ORM\Table(name: 'employe')]
class Employe
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

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $cin = null;

    public function getCin(): ?int
    {
        return $this->cin;
    }

    public function setCin(?int $cin): self
    {
        $this->cin = $cin;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom = null;

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $prenom = null;

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $password = null;

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $hiring_date = null;

    public function getHiring_date(): ?\DateTimeInterface
    {
        return $this->hiring_date;
    }

    public function setHiring_date(?\DateTimeInterface $hiring_date): self
    {
        $this->hiring_date = $hiring_date;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $soldeConges = null;

    public function getSoldeConges(): ?int
    {
        return $this->soldeConges;
    }

    public function setSoldeConges(?int $soldeConges): self
    {
        $this->soldeConges = $soldeConges;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Absence::class, mappedBy: 'employe')]
    private Collection $absences;

    /**
     * @return Collection<int, Absence>
     */
    public function getAbsences(): Collection
    {
        if (!$this->absences instanceof Collection) {
            $this->absences = new ArrayCollection();
        }
        return $this->absences;
    }

    public function addAbsence(Absence $absence): self
    {
        if (!$this->getAbsences()->contains($absence)) {
            $this->getAbsences()->add($absence);
        }
        return $this;
    }

    public function removeAbsence(Absence $absence): self
    {
        $this->getAbsences()->removeElement($absence);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: DemandeConge::class, mappedBy: 'employe')]
    private Collection $demandeConges;

    /**
     * @return Collection<int, DemandeConge>
     */
    public function getDemandeConges(): Collection
    {
        if (!$this->demandeConges instanceof Collection) {
            $this->demandeConges = new ArrayCollection();
        }
        return $this->demandeConges;
    }

    public function addDemandeConge(DemandeConge $demandeConge): self
    {
        if (!$this->getDemandeConges()->contains($demandeConge)) {
            $this->getDemandeConges()->add($demandeConge);
        }
        return $this;
    }

    public function removeDemandeConge(DemandeConge $demandeConge): self
    {
        $this->getDemandeConges()->removeElement($demandeConge);
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Formation::class, inversedBy: 'employes')]
    #[ORM\JoinTable(
        name: 'formation_participation',
        joinColumns: [
            new ORM\JoinColumn(name: 'employe_id', referencedColumnName: 'id')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'formation_id', referencedColumnName: 'id')
        ]
    )]
    private Collection $formations;

    /**
     * @return Collection<int, Formation>
     */
    public function getFormations(): Collection
    {
        if (!$this->formations instanceof Collection) {
            $this->formations = new ArrayCollection();
        }
        return $this->formations;
    }

    public function addFormation(Formation $formation): self
    {
        if (!$this->getFormations()->contains($formation)) {
            $this->getFormations()->add($formation);
        }
        return $this;
    }

    public function removeFormation(Formation $formation): self
    {
        $this->getFormations()->removeElement($formation);
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Quiz::class, inversedBy: 'employes')]
    #[ORM\JoinTable(
        name: 'quiz_reponses',
        joinColumns: [
            new ORM\JoinColumn(name: 'employe_id', referencedColumnName: 'id')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'quiz_id', referencedColumnName: 'id')
        ]
    )]
    private Collection $quizs;

    public function __construct()
    {
        $this->absences = new ArrayCollection();
        $this->demandeConges = new ArrayCollection();
        $this->formations = new ArrayCollection();
        $this->quizs = new ArrayCollection();
    }

    /**
     * @return Collection<int, Quiz>
     */
    public function getQuizs(): Collection
    {
        if (!$this->quizs instanceof Collection) {
            $this->quizs = new ArrayCollection();
        }
        return $this->quizs;
    }

    public function addQuiz(Quiz $quiz): self
    {
        if (!$this->getQuizs()->contains($quiz)) {
            $this->getQuizs()->add($quiz);
        }
        return $this;
    }

    public function removeQuiz(Quiz $quiz): self
    {
        $this->getQuizs()->removeElement($quiz);
        return $this;
    }

    public function getHiringDate(): ?\DateTimeInterface
    {
        return $this->hiring_date;
    }

    public function setHiringDate(?\DateTimeInterface $hiring_date): static
    {
        $this->hiring_date = $hiring_date;

        return $this;
    }

}
