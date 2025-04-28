<?php

namespace App\Entity;

use App\Repository\TicketReclamationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TicketReclamationRepository::class)]
class TicketReclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Reclamation::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Reclamation must be selected.')]
    private ?Reclamation $reclamation = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'HR Staff Name is required.')]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z\s]+$/', 
        message: 'HR Staff Name must contain only letters and spaces.'
    )]
    private ?string $hrStaffName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: 'Response message is required.')]
    #[Assert\Length(
        max: 500,
        maxMessage: 'Response message must be at most {{ limit }} characters.'
    )]
    private ?string $responseMessage = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull(message: 'Date of response is required.')]
    #[Assert\LessThanOrEqual('today', message: 'Date of response cannot be in the future.')]
    private ?\DateTimeInterface $dateOfResponse = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: 'Action taken must be at most {{ limit }} characters.'
    )]
    private ?string $actionTaken = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'Resolution status is required.')]
    #[Assert\Choice(
        choices: ['Resolved', 'Pending', 'Closed'],
        message: 'Choose a valid resolution status: Resolved, Pending, or Closed.'
    )]
    private ?string $resolutionStatus = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReclamation(): ?Reclamation
    {
        return $this->reclamation;
    }

    public function setReclamation(?Reclamation $reclamation): self
    {
        $this->reclamation = $reclamation;
        return $this;
    }

    public function getHrStaffName(): ?string
    {
        return $this->hrStaffName;
    }

    public function setHrStaffName(string $hrStaffName): self
    {
        $this->hrStaffName = $hrStaffName;
        return $this;
    }

    public function getResponseMessage(): ?string
    {
        return $this->responseMessage;
    }

    public function setResponseMessage(?string $responseMessage): self
    {
        $this->responseMessage = $responseMessage;
        return $this;
    }

    public function getDateOfResponse(): ?\DateTimeInterface
    {
        return $this->dateOfResponse;
    }

    public function setDateOfResponse(\DateTimeInterface $dateOfResponse): self
    {
        $this->dateOfResponse = $dateOfResponse;
        return $this;
    }

    public function getActionTaken(): ?string
    {
        return $this->actionTaken;
    }

    public function setActionTaken(?string $actionTaken): self
    {
        $this->actionTaken = $actionTaken;
        return $this;
    }

    public function getResolutionStatus(): ?string
    {
        return $this->resolutionStatus;
    }

    public function setResolutionStatus(?string $resolutionStatus): self
    {
        $this->resolutionStatus = $resolutionStatus;
        return $this;
    }
}
