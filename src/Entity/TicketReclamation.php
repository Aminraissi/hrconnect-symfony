<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\TicketReclamationRepository;

#[ORM\Entity(repositoryClass: TicketReclamationRepository::class)]
#[ORM\Table(name: 'ticket_reclamation')]
class TicketReclamation
{
    #[ORM\Column(type: 'integer', nullable: false)]
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

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $reclamation_id = null;

    public function getReclamation_id(): ?int
    {
        return $this->reclamation_id;
    }

    public function setReclamation_id(int $reclamation_id): self
    {
        $this->reclamation_id = $reclamation_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $hr_staff_name = null;

    public function getHr_staff_name(): ?string
    {
        return $this->hr_staff_name;
    }

    public function setHr_staff_name(string $hr_staff_name): self
    {
        $this->hr_staff_name = $hr_staff_name;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $response_message = null;

    public function getResponse_message(): ?string
    {
        return $this->response_message;
    }

    public function setResponse_message(?string $response_message): self
    {
        $this->response_message = $response_message;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_of_response = null;

    public function getDate_of_response(): ?\DateTimeInterface
    {
        return $this->date_of_response;
    }

    public function setDate_of_response(\DateTimeInterface $date_of_response): self
    {
        $this->date_of_response = $date_of_response;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $action_taken = null;

    public function getAction_taken(): ?string
    {
        return $this->action_taken;
    }

    public function setAction_taken(?string $action_taken): self
    {
        $this->action_taken = $action_taken;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $resolution_status = null;

    public function getResolution_status(): ?string
    {
        return $this->resolution_status;
    }

    public function setResolution_status(?string $resolution_status): self
    {
        $this->resolution_status = $resolution_status;
        return $this;
    }

    public function getReclamationId(): ?int
    {
        return $this->reclamation_id;
    }

    public function setReclamationId(int $reclamation_id): static
    {
        $this->reclamation_id = $reclamation_id;

        return $this;
    }

    public function getHrStaffName(): ?string
    {
        return $this->hr_staff_name;
    }

    public function setHrStaffName(string $hr_staff_name): static
    {
        $this->hr_staff_name = $hr_staff_name;

        return $this;
    }

    public function getResponseMessage(): ?string
    {
        return $this->response_message;
    }

    public function setResponseMessage(?string $response_message): static
    {
        $this->response_message = $response_message;

        return $this;
    }

    public function getDateOfResponse(): ?\DateTimeInterface
    {
        return $this->date_of_response;
    }

    public function setDateOfResponse(\DateTimeInterface $date_of_response): static
    {
        $this->date_of_response = $date_of_response;

        return $this;
    }

    public function getActionTaken(): ?string
    {
        return $this->action_taken;
    }

    public function setActionTaken(?string $action_taken): static
    {
        $this->action_taken = $action_taken;

        return $this;
    }

    public function getResolutionStatus(): ?string
    {
        return $this->resolution_status;
    }

    public function setResolutionStatus(?string $resolution_status): static
    {
        $this->resolution_status = $resolution_status;

        return $this;
    }

}
