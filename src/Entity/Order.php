<?php

namespace App\Entity;

use App\Traits\BlameableEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: '`order`')]
class Order
{
    const DRAFT = 0;
    const ORDERED = 1;
    const PROCESSING = 2;
    const COMPLETED = 3;
    const CANCELLED = 4;
    const STATUS = [
        self::DRAFT => "Draft",
        self::ORDERED => "Ordered",
        self::PROCESSING => "Processing",
        self::COMPLETED => "Completed",
        self::CANCELLED => "Cancelled",
    ];

    use BlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private $customer;

    #[ORM\Column(length: 127, nullable: true)]
    private $email;

    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private $shipAddress;

    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private $billAddress;

    #[ORM\Column(length: 63, nullable: true)]
    private $shippingName;

    #[ORM\Column(length: 15, nullable: true)]
    private $shippingPhone;

    #[ORM\Column(type: "text", nullable: true)]
    private $shippingAddress;

    #[ORM\Column(type: "text", nullable: true)]
    private $shippingStreet;

    #[ORM\Column(type: "text", nullable: true)]
    private $shippingState;

    #[ORM\Column(length: "31", nullable: true)]
    private $shippingCity;

    #[ORM\Column(length: "7", nullable: true)]
    private $shippingPincode;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $billingSame = true;

    #[ORM\Column(length: 63, nullable: true)]
    private $billingName;

    #[ORM\Column(length: 15, nullable: true)]
    private $billingPhone;

    #[ORM\Column(type: "text", nullable: true)]
    private $billingAddress;

    #[ORM\Column(type: "text", nullable: true)]
    private $billingStreet;

    #[ORM\Column(type: "text", nullable: true)]
    private $billingState;

    #[ORM\Column(length: "31", nullable: true)]
    private $billingCity;

    #[ORM\Column(length: "7", nullable: true)]
    private $billingPincode;

    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ["persist"], orphanRemoval: true)]
    private $items;

    #[ORM\OneToOne(targetEntity: Transaction::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    private $transaction;

    #[ORM\Column(type: 'float', options: ['default' => '0'])]
    private $subtotal = 0;

    #[ORM\Column(type: "json", nullable: true)]
    private $tax;

    #[ORM\Column(type: 'float', options: ['default' => '0'])]
    private $shipping = 0;

    #[ORM\Column(type: 'float', options: ['default' => '0'])]
    private $discount = 0;

    #[ORM\Column(type: 'float', options: ['default' => '0'])]
    private $total = 0;

    #[ORM\OneToMany(targetEntity: SalesOrder::class, mappedBy: 'order', cascade: ["persist"], orphanRemoval: true)]
    private $salesOrders;

    #[ORM\Column(type: 'smallint', nullable: false, options: ['default' => self::DRAFT])]
    private $status = self::DRAFT;
    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->salesOrders = new ArrayCollection();
    }

    public function getOrderNumber(): string
    {
        return "O-".sprintf('%06d', $this->id);
    }

    public function __toString()
    {
        return $this->getOrderNumber();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getShippingName(): ?string
    {
        return $this->shippingName;
    }

    public function setShippingName(?string $shippingName): static
    {
        $this->shippingName = $shippingName;

        return $this;
    }

    public function getShippingPhone(): ?string
    {
        return $this->shippingPhone;
    }

    public function setShippingPhone(?string $shippingPhone): static
    {
        $this->shippingPhone = $shippingPhone;

        return $this;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?string $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getShippingStreet(): ?string
    {
        return $this->shippingStreet;
    }

    public function setShippingStreet(?string $shippingStreet): static
    {
        $this->shippingStreet = $shippingStreet;

        return $this;
    }

    public function getShippingState(): ?string
    {
        return $this->shippingState;
    }

    public function setShippingState(?string $shippingState): static
    {
        $this->shippingState = $shippingState;

        return $this;
    }

    public function getShippingCity(): ?string
    {
        return $this->shippingCity;
    }

    public function setShippingCity(?string $shippingCity): static
    {
        $this->shippingCity = $shippingCity;

        return $this;
    }

    public function getShippingPincode(): ?string
    {
        return $this->shippingPincode;
    }

    public function setShippingPincode(?string $shippingPincode): static
    {
        $this->shippingPincode = $shippingPincode;

        return $this;
    }

    public function isBillingSame(): ?bool
    {
        return $this->billingSame;
    }

    public function setBillingSame(bool $billingSame): static
    {
        $this->billingSame = $billingSame;

        return $this;
    }

    public function getBillingName(): ?string
    {
        return $this->billingName;
    }

    public function setBillingName(?string $billingName): static
    {
        $this->billingName = $billingName;

        return $this;
    }

    public function getBillingPhone(): ?string
    {
        return $this->billingPhone;
    }

    public function setBillingPhone(?string $billingPhone): static
    {
        $this->billingPhone = $billingPhone;

        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?string $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getBillingStreet(): ?string
    {
        return $this->billingStreet;
    }

    public function setBillingStreet(?string $billingStreet): static
    {
        $this->billingStreet = $billingStreet;

        return $this;
    }

    public function getBillingState(): ?string
    {
        return $this->billingState;
    }

    public function setBillingState(?string $billingState): static
    {
        $this->billingState = $billingState;

        return $this;
    }

    public function getBillingCity(): ?string
    {
        return $this->billingCity;
    }

    public function setBillingCity(?string $billingCity): static
    {
        $this->billingCity = $billingCity;

        return $this;
    }

    public function getBillingPincode(): ?string
    {
        return $this->billingPincode;
    }

    public function setBillingPincode(?string $billingPincode): static
    {
        $this->billingPincode = $billingPincode;

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

    public function getTax(): ?array
    {
        return $this->tax;
    }

    public function setTax(?array $tax): static
    {
        $this->tax = $tax;

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

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getShipAddress(): ?Address
    {
        return $this->shipAddress;
    }

    public function setShipAddress(?Address $shipAddress): static
    {
        $this->shipAddress = $shipAddress;

        return $this;
    }

    public function getBillAddress(): ?Address
    {
        return $this->billAddress;
    }

    public function setBillAddress(?Address $billAddress): static
    {
        $this->billAddress = $billAddress;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }

        return $this;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): static
    {
        // unset the owning side of the relation if necessary
        if ($transaction === null && $this->transaction !== null) {
            $this->transaction->setOrder(null);
        }

        // set the owning side of the relation if necessary
        if ($transaction !== null && $transaction->getOrder() !== $this) {
            $transaction->setOrder($this);
        }

        $this->transaction = $transaction;

        return $this;
    }

    /**
     * @return Collection<int, SalesOrder>
     */
    public function getSalesOrders(): Collection
    {
        return $this->salesOrders;
    }

    public function addSalesOrder(SalesOrder $salesOrder): static
    {
        if (!$this->salesOrders->contains($salesOrder)) {
            $this->salesOrders->add($salesOrder);
            $salesOrder->setOrder($this);
        }

        return $this;
    }

    public function removeSalesOrder(SalesOrder $salesOrder): static
    {
        if ($this->salesOrders->removeElement($salesOrder)) {
            // set the owning side to null (unless already changed)
            if ($salesOrder->getOrder() === $this) {
                $salesOrder->setOrder(null);
            }
        }

        return $this;
    }
}
