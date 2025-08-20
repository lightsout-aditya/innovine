<?php

namespace App\Entity;

use App\Traits\BlameableEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
class SalesOrder
{
    const DRAFT = 0;
    const CONFIRMED = 1;
    const VOID = 2;
    const STATUS = [
        self::DRAFT => "Draft",
        self::CONFIRMED => "Confirmed",
        self::VOID => "Void",
    ];

    use BlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'salesOrders')]
    private $order;

    #[ORM\OneToMany(mappedBy: 'salesOrder', targetEntity: Invoice::class, cascade: ["persist"], orphanRemoval: true)]
    private $invoices;

    #[ORM\OneToMany(mappedBy: 'salesOrder', targetEntity: Package::class, cascade: ["persist"], orphanRemoval: true)]
    private $packages;

    #[ORM\Column(length: 31, unique: true, nullable: true)]
    private $zohoId;

    #[ORM\Column(length: 31, nullable: true)]
    private $soNumber;

    #[ORM\OneToMany(mappedBy: 'salesOrder', targetEntity: SalesOrderItem::class, cascade: ["persist"], orphanRemoval: true)]
    private $items;

    #[ORM\Column(type: "json", nullable: true)]
    private $taxes;

    #[ORM\Column(type: 'float', options: ['default' => '0'])]
    private $subtotal = 0;

    #[ORM\Column(type: 'float', options: ['default' => '0'])]
    private $shipping = 0;

    #[ORM\Column(type: 'float', options: ['default' => '0'])]
    private $discount = 0;

    #[ORM\Column(type: 'float', options: ['default' => '0'])]
    private $tax = 0;

    #[ORM\Column(type: 'float', options: ['default' => '0'])]
    private $total = 0;

    #[ORM\Column(type: 'smallint', nullable: false, options: ['default' => self::DRAFT])]
    private $status = self::DRAFT;

    public function __toString()
    {
        return "{$this->soNumber}";
    }

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->packages = new ArrayCollection();
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

    public function getSoNumber(): ?string
    {
        return $this->soNumber;
    }

    public function setSoNumber(?string $soNumber): static
    {
        $this->soNumber = $soNumber;

        return $this;
    }

    public function getTaxes(): ?array
    {
        return $this->taxes;
    }

    public function setTaxes(?array $taxes): static
    {
        $this->taxes = $taxes;

        return $this;
    }

    public function getSubtotal(): ?float
    {
        return $this->subtotal;
    }

    public function setSubtotal(float $subtotal): static
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    public function getShipping(): ?float
    {
        return $this->shipping;
    }

    public function setShipping(float $shipping): static
    {
        $this->shipping = $shipping;

        return $this;
    }

    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    public function setDiscount(float $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    public function getTax(): ?float
    {
        return $this->tax;
    }

    public function setTax(float $tax): static
    {
        $this->tax = $tax;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setSalesOrder($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            // set the owning side to null (unless already changed)
            if ($invoice->getSalesOrder() === $this) {
                $invoice->setSalesOrder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Package>
     */
    public function getPackages(): Collection
    {
        return $this->packages;
    }

    public function addPackage(Package $package): static
    {
        if (!$this->packages->contains($package)) {
            $this->packages->add($package);
            $package->setSalesOrder($this);
        }

        return $this;
    }

    public function removePackage(Package $package): static
    {
        if ($this->packages->removeElement($package)) {
            // set the owning side to null (unless already changed)
            if ($package->getSalesOrder() === $this) {
                $package->setSalesOrder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SalesOrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(SalesOrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setSalesOrder($this);
        }

        return $this;
    }

    public function removeItem(SalesOrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getSalesOrder() === $this) {
                $item->setSalesOrder(null);
            }
        }

        return $this;
    }
}
