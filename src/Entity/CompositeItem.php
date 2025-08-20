<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[UniqueEntity(fields: ['compositeItem', 'item'], message: 'Duplicate item in Bundle', errorPath: 'item', ignoreNull: false)]
class CompositeItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'compositeItems')]
    private $compositeItem;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    private $item;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 1])]
    private $quantity = 1;

    public function __toString()
    {
        return "{$this->item}";
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getCompositeItem(): ?Item
    {
        return $this->compositeItem;
    }

    public function setCompositeItem(?Item $compositeItem): static
    {
        $this->compositeItem = $compositeItem;

        return $this;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): static
    {
        $this->item = $item;

        return $this;
    }
}
