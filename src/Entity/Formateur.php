<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\FormateurRepository;

#[ORM\Entity(repositoryClass: FormateurRepository::class)]
#[ORM\Table(name: 'formateurs')]
class Formateur
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

    #[ORM\OneToMany(targetEntity: Formation::class, mappedBy: 'formateur')]
    private Collection $formations;

    public function __construct()
    {
        $this->formations = new ArrayCollection();
    }

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

}
