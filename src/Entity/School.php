<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[UniqueEntity(fields: ['name'], message: 'This name is already in use.')]
#[UniqueEntity(fields: ['schoolCode'], message: 'This School Code is already in use.')]
class School
{
    public const BRANDS = [
        'BOMIS' => 'BOMIS',
        'BOMPS' => 'BOMPS',
        'GBMS' => 'GBMS',
        'BCPGIS' => 'BCPGIS',
        'BOMT' => 'BOMT',
        'COCO' => 'COCO',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $schoolCode = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $brandCode = null;

    // --- NEW ZONE FIELD START ---
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $zone = null;
    // --- NEW ZONE FIELD END ---

    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(unique: true)]
    private ?string $slug;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $active = false;

    #[ORM\ManyToMany(targetEntity: Item::class, inversedBy: 'schools', cascade: ["persist"])]
    private $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }
    
    public function __toString()
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSchoolCode(): ?string
    {
        return $this->schoolCode;
    }

    public function setSchoolCode(?string $schoolCode): static
    {
        $this->schoolCode = $schoolCode;
        return $this;
    }

    public function getBrandCode(): ?string
    {
        return $this->brandCode;
    }

    public function setBrandCode(?string $brandCode): static
    {
        $this->brandCode = $brandCode;
        return $this;
    }

    // --- NEW GETTER/SETTER FOR ZONE ---
    public function getZone(): ?string
    {
        return $this->zone;
    }

    public function setZone(?string $zone): static
    {
        $this->zone = $zone;
        return $this;
    }
    // ----------------------------------

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
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

    /**
     * @return Collection<int, Item>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
        }
        return $this;
    }

    public function removeItem(Item $item): static
    {
        $this->items->removeElement($item);
        return $this;
    }
}