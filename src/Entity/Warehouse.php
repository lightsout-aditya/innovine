<?php

namespace App\Entity;

use App\Traits\BlameableEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[UniqueEntity(fields: ['name'], message: 'Warehouse name already exists.')]
class Warehouse
{
    use BlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(length: 31, nullable: true)]
    private $zohoId;

    #[ORM\Column(nullable: false)]
    private $name;

    #[ORM\Column(length: 127, nullable: true)]
    private $email;

    #[ORM\Column(length: 63, nullable: true)]
    private $phone;

    #[ORM\Column(length: 255, nullable: true)]
    private $address;

    #[ORM\Column(length: 31, nullable: true)]
    private $city;

    #[ORM\Column(length: 31, nullable: true)]
    private $state;

    #[ORM\Column(length: 15, nullable: true)]
    private $zip;

    #[ORM\Column(name: "`primary`", type: 'boolean', nullable: false, options: ['default' => false])]
    private $primary = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private $active = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private $imported = false;

    public function __toString()
    {
        return "{$this->name}";
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getZohoId(): ?string
    {
        return $this->zohoId;
    }

    public function setZohoId(?string $zohoId): static
    {
        $this->zohoId = $zohoId;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): static
    {
        $this->zip = $zip;

        return $this;
    }

    public function isPrimary(): ?bool
    {
        return $this->primary;
    }

    public function setPrimary(bool $primary): static
    {
        $this->primary = $primary;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function isImported(): ?bool
    {
        return $this->imported;
    }

    public function setImported(bool $imported): static
    {
        $this->imported = $imported;

        return $this;
    }
}
