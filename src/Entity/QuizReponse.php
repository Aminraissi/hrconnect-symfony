<?php
namespace App\Entity;

use App\Repository\QuizReponseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizReponseRepository::class)]
#[ORM\Table(name: 'quiz_reponses')]
class QuizReponse
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Quiz::class)]
    #[ORM\JoinColumn(name: 'quiz_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Quiz $quiz = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'employe_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $employe = null;

    #[ORM\Column(type: 'integer')]
    private ?int $numReponse = null;

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): self
    {
        $this->quiz = $quiz;
        return $this;
    }

    public function getEmploye(): ?User
    {
        return $this->employe;
    }

    public function setEmploye(?User $employe): self
    {
        $this->employe = $employe;
        return $this;
    }

    public function getNumReponse(): ?int
    {
        return $this->numReponse;
    }

    public function setNumReponse(int $numReponse): self
    {
        $this->numReponse = $numReponse;
        return $this;
    }
}
