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

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $cin = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $prenom = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $hiring_date = null;

    #[ORM\Column(name: 'soldeConges', type: 'integer', nullable: true)] // Explicitly map the column name
    private ?int $soldeConges = null;

    #[ORM\OneToMany(targetEntity: Absence::class, mappedBy: 'employe')]
    private Collection $absences;

    #[ORM\OneToMany(targetEntity: DemandeConge::class, mappedBy: 'employe')]
    private Collection $demandeConges;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCin(): ?int
    {
        return $this->cin;
    }

    public function setCin(?int $cin): self
    {
        $this->cin = $cin;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getHiringDate(): ?\DateTimeInterface
    {
        return $this->hiring_date;
    }

    public function setHiringDate(?\DateTimeInterface $hiring_date): self
    {
        $this->hiring_date = $hiring_date;
        return $this;
    }

    public function getSoldeConges(): ?int
    {
        return $this->soldeConges;
    }

    public function setSoldeConges(?int $soldeConges): self
    {
        $this->soldeConges = $soldeConges;
        return $this;
    }

    public function getAbsences(): Collection
    {
        return $this->absences;
    }

    public function addAbsence(Absence $absence): self
    {
        if (!$this->absences->contains($absence)) {
            $this->absences->add($absence);
        }
        return $this;
    }

    public function removeAbsence(Absence $absence): self
    {
        $this->absences->removeElement($absence);
        return $this;
    }

    public function getDemandeConges(): Collection
    {
        return $this->demandeConges;
    }

    public function addDemandeConge(DemandeConge $demandeConge): self
    {
        if (!$this->demandeConges->contains($demandeConge)) {
            $this->demandeConges->add($demandeConge);
        }
        return $this;
    }

    public function removeDemandeConge(DemandeConge $demandeConge): self
    {
        $this->demandeConges->removeElement($demandeConge);
        return $this;
    }

    public function getFormations(): Collection
    {
        return $this->formations;
    }

    public function addFormation(Formation $formation): self
    {
        if (!$this->formations->contains($formation)) {
            $this->formations->add($formation);
        }
        return $this;
    }

    public function removeFormation(Formation $formation): self
    {
        $this->formations->removeElement($formation);
        return $this;
    }

    public function getQuizs(): Collection
    {
        return $this->quizs;
    }

    public function addQuiz(Quiz $quiz): self
    {
        if (!$this->quizs->contains($quiz)) {
            $this->quizs->add($quiz);
        }
        return $this;
    }

    public function removeQuiz(Quiz $quiz): self
    {
        $this->quizs->removeElement($quiz);
        return $this;
    }
}