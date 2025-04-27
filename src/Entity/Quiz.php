<?php
namespace App\Entity;

use App\Repository\QuizRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
#[ORM\Table(name: 'quiz')]
class Quiz
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

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'quizs')]
    #[ORM\JoinColumn(name: 'formation_id', referencedColumnName: 'id')]
    private ?Formation $formation = null;

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): self
    {
        $this->formation = $formation;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'The question is required.')]
    #[Assert\Length(max: 255, maxMessage: 'The question must not exceed 255 characters.')]
    private ?string $question = null;

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'The first answer is required.')]
    private ?string $reponse1 = null;

    public function getReponse1(): ?string
    {
        return $this->reponse1;
    }

    public function setReponse1(string $reponse1): self
    {
        $this->reponse1 = $reponse1;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'The second answer is required.')]
    private ?string $reponse2 = null;

    public function getReponse2(): ?string
    {
        return $this->reponse2;
    }

    public function setReponse2(?string $reponse2): self
    {
        $this->reponse2 = $reponse2;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'The third answer is required.')]
    private ?string $reponse3 = null;

    public function getReponse3(): ?string
    {
        return $this->reponse3;
    }

    public function setReponse3(?string $reponse3): self
    {
        $this->reponse3 = $reponse3;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotBlank(message: 'The correct answer number is required.')]
    #[Assert\Range(min: 1, max: 3, notInRangeMessage: 'The correct answer number must be between {{ min }} and {{ max }}.')]

    private ?int $num_reponse_correct = null;

    public function getNum_reponse_correct(): ?int
    {
        return $this->num_reponse_correct;
    }

    public function setNum_reponse_correct(int $num_reponse_correct): self
    {
        $this->num_reponse_correct = $num_reponse_correct;
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'quizs')]
    #[ORM\JoinTable(
        name: 'quiz_reponses',
        joinColumns: [
            new ORM\JoinColumn(name: 'quiz_id', referencedColumnName: 'id'),
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'employe_id', referencedColumnName: 'id'),
        ]
    )]
    // private Collection $employes;

    public function __construct()
    {
        // $this->employes = new ArrayCollection();
    }

    // /**
    //  * @return Collection<int, Employe>
    //  */
    // public function getEmployes(): Collection
    // {
    //     if (! $this->employes instanceof Collection) {
    //         $this->employes = new ArrayCollection();
    //     }
    //     return $this->employes;
    // }

    // public function addEmploye(Employe $employe): self
    // {
    //     if (! $this->getEmployes()->contains($employe)) {
    //         $this->getEmployes()->add($employe);
    //     }
    //     return $this;
    // }

    // public function removeEmploye(Employe $employe): self
    // {
    //     $this->getEmployes()->removeElement($employe);
    //     return $this;
    // }

    public function getNumReponseCorrect(): ?int
    {
        return $this->num_reponse_correct;
    }

    public function setNumReponseCorrect(int $num_reponse_correct): static
    {
        $this->num_reponse_correct = $num_reponse_correct;

        return $this;
    }

}
