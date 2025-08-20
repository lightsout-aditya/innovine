<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class SalesOrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: SalesOrder::class, inversedBy: 'items')]
    private $salesOrder;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    private $item;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $quantity;

    #[ORM\Column(type: 'float', options: ['default' => '0'])]
    private $price = 0;

    public function getTotal(): float|int
    {
        return $this->price * $this->quantity;
    }

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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getSalesOrder(): ?SalesOrder
    {
        return $this->salesOrder;
    }

    public function setSalesOrder(?SalesOrder $salesOrder): static
    {
        $this->salesOrder = $salesOrder;

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
