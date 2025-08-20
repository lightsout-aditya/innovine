<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[UniqueEntity(fields: ['name'], message: 'Item Group name already exists.')]
class ItemGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(length: 31, nullable: true)]
    private $zohoId;

    #[ORM\OneToMany(mappedBy: 'group', targetEntity: Item::class)]
    #[ORM\OrderBy(['size' => 'ASC'])]
    private $items;

    #[ORM\Column(nullable: false)]
    private $name;

    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(unique: true)]
    private $slug;

    #[ORM\Column(length: 15, nullable: true)]
    private $unit;

    #[ORM\Column(length: 63, nullable: true)]
    private $sizeChart;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private $active = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private $imported = false;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getActiveVisibleItems(): Collection
    {
        return $this->items->filter(function (Item $item) {
            return $item->isActive() and $item->isShowInPortal();
        });
    }

    public function getActiveItems(): Collection
    {
        return $this->items->filter(function (Item $item) {
            return $item->isActive();
        });
    }

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getSizeChart(): ?string
    {
        return $this->sizeChart;
    }

    public function setSizeChart(?string $sizeChart): static
    {
        $this->sizeChart = $sizeChart;

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
            $item->setGroup($this);
        }

        return $this;
    }

    public function removeItem(Item $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getGroup() === $this) {
                $item->setGroup(null);
            }
        }

        return $this;
    }
}
