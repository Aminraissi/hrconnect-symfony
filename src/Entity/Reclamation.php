<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ReclamationRepository;

#[ORM\Entity(repositoryClass: ReclamationRepository::class)]
#[ORM\Table(name: 'reclamation')]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $employee_name = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type = null;

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_of_submission = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $priority = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getEmployee_name(): ?string
    {
        return $this->employee_name;
    }

    public function setEmployee_name(string $employee_name): self
    {
        $this->employee_name = $employee_name;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDate_of_submission(): ?\DateTimeInterface
    {
        return $this->date_of_submission;
    }

    public function setDate_of_submission(\DateTimeInterface $date_of_submission): self
    {
        $this->date_of_submission = $date_of_submission;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getEmployeeName(): ?string
    {
        return $this->employee_name;
    }

    public function setEmployeeName(string $employee_name): static
    {
        $this->employee_name = $employee_name;

        return $this;
    }

    public function getDateOfSubmission(): ?\DateTimeInterface
    {
        return $this->date_of_submission;
    }

    public function setDateOfSubmission(\DateTimeInterface $date_of_submission): static
    {
        $this->date_of_submission = $date_of_submission;

        return $this;
    }
}
