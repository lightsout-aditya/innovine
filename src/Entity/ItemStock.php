<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ItemStock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'stocks')]
    private $item;

    #[ORM\ManyToOne(targetEntity: Warehouse::class)]
    private $warehouse;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $stockOnHand = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $availableStock = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $actualAvailableStock = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $committedStock = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $actualCommittedStock = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $availableForSaleStock = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $actualAvailableForSaleStock = 0;

    public function __toString()
    {
        return "{$this->warehouse}";
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStockOnHand(): ?int
    {
        return $this->stockOnHand;
    }

    public function setStockOnHand(int $stockOnHand): static
    {
        $this->stockOnHand = $stockOnHand;

        return $this;
    }

    public function getAvailableStock(): ?int
    {
        return $this->availableStock;
    }

    public function setAvailableStock(int $availableStock): static
    {
        $this->availableStock = $availableStock;

        return $this;
    }

    public function getActualAvailableStock(): ?int
    {
        return $this->actualAvailableStock;
    }

    public function setActualAvailableStock(int $actualAvailableStock): static
    {
        $this->actualAvailableStock = $actualAvailableStock;

        return $this;
    }

    public function getCommittedStock(): ?int
    {
        return $this->committedStock;
    }

    public function setCommittedStock(int $committedStock): static
    {
        $this->committedStock = $committedStock;

        return $this;
    }

    public function getActualCommittedStock(): ?int
    {
        return $this->actualCommittedStock;
    }

    public function setActualCommittedStock(int $actualCommittedStock): static
    {
        $this->actualCommittedStock = $actualCommittedStock;

        return $this;
    }

    public function getAvailableForSaleStock(): ?int
    {
        return $this->availableForSaleStock;
    }

    public function setAvailableForSaleStock(int $availableForSaleStock): static
    {
        $this->availableForSaleStock = $availableForSaleStock;

        return $this;
    }

    public function getActualAvailableForSaleStock(): ?int
    {
        return $this->actualAvailableForSaleStock;
    }

    public function setActualAvailableForSaleStock(int $actualAvailableForSaleStock): static
    {
        $this->actualAvailableForSaleStock = $actualAvailableForSaleStock;

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

    public function getWarehouse(): ?Warehouse
    {
        return $this->warehouse;
    }

    public function setWarehouse(?Warehouse $warehouse): static
    {
        $this->warehouse = $warehouse;

        return $this;
    }
}
