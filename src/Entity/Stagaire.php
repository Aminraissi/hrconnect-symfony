<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\StagaireRepository;

#[ORM\Entity(repositoryClass: StagaireRepository::class)]
#[ORM\Table(name: 'stagaires')]
class Stagaire
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $first_name = null;

    public function getFirst_name(): ?string
    {
        return $this->first_name;
    }

    public function setFirst_name(string $first_name): self
    {
        $this->first_name = $first_name;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $last_name = null;

    public function getLast_name(): ?string
    {
        return $this->last_name;
    }

    public function setLast_name(string $last_name): self
    {
        $this->last_name = $last_name;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $password = null;

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $debut_stage = null;

    public function getDebut_stage(): ?\DateTimeInterface
    {
        return $this->debut_stage;
    }

    public function setDebut_stage(\DateTimeInterface $debut_stage): self
    {
        $this->debut_stage = $debut_stage;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $fin_stage = null;

    public function getFin_stage(): ?\DateTimeInterface
    {
        return $this->fin_stage;
    }

    public function setFin_stage(\DateTimeInterface $fin_stage): self
    {
        $this->fin_stage = $fin_stage;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getDebutStage(): ?\DateTimeInterface
    {
        return $this->debut_stage;
    }

    public function setDebutStage(\DateTimeInterface $debut_stage): static
    {
        $this->debut_stage = $debut_stage;

        return $this;
    }

    public function getFinStage(): ?\DateTimeInterface
    {
        return $this->fin_stage;
    }

    public function setFinStage(\DateTimeInterface $fin_stage): static
    {
        $this->fin_stage = $fin_stage;

        return $this;
    }

}
