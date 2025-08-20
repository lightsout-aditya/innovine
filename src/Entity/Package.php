<?php

namespace App\Entity;

use App\Traits\BlameableEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
class Package
{
    const PACKED = 1;
    const SHIPPED = 2;
    const OUT_FOR_DELIVERY = 3;
    const DELIVERED = 4;
    const STATUS = [
        self::PACKED => "Packed",
        self::SHIPPED => "Shipped",
        self::OUT_FOR_DELIVERY => "Out For Delivery",
        self::DELIVERED => "Delivered",
    ];

    const COURIER_PARTNER = ['SaFex', 'BlueDart'];

    use BlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: SalesOrder::class, inversedBy: 'packages')]
    private $salesOrder;

    #[ORM\Column(length: 31, unique: true, nullable: true)]
    private $zohoId;

    #[ORM\Column(length: 31, nullable: true)]
    private $packageNumber;

    #[ORM\Column(length: 31, nullable: true)]
    private $shipmentNumber;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    private $quantity = 0;

    #[ORM\Column(length: 31, nullable: true)]
    private $trackingNumber;

    #[ORM\Column(length: 15, nullable: true)]
    private $carrier;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $delivered = false;

    #[ORM\Column(type: "datetime", nullable: true)]
    private $deliveryDate;

    #[ORM\Column(type: 'smallint', nullable: false, options: ['default' => self::SHIPPED])]
    private $status = self::SHIPPED;

    #[ORM\OneToOne(mappedBy: 'package', targetEntity: OrderReturn::class)]
    private $return;

    public function __toString()
    {
        return "{$this->shipmentNumber}";
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

    public function getPackageNumber(): ?string
    {
        return $this->packageNumber;
    }

    public function setPackageNumber(?string $packageNumber): static
    {
        $this->packageNumber = $packageNumber;

        return $this;
    }

    public function getShipmentNumber(): ?string
    {
        return $this->shipmentNumber;
    }

    public function setShipmentNumber(?string $shipmentNumber): static
    {
        $this->shipmentNumber = $shipmentNumber;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function setCarrier(?string $carrier): static
    {
        $this->carrier = $carrier;

        return $this;
    }

    public function isDelivered(): ?bool
    {
        return $this->delivered;
    }

    public function setDelivered(bool $delivered): static
    {
        $this->delivered = $delivered;

        return $this;
    }

    public function getDeliveryDate(): ?\DateTimeInterface
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(?\DateTimeInterface $deliveryDate): static
    {
        $this->deliveryDate = $deliveryDate;

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

    public function getSalesOrder(): ?SalesOrder
    {
        return $this->salesOrder;
    }

    public function setSalesOrder(?SalesOrder $salesOrder): static
    {
        $this->salesOrder = $salesOrder;

        return $this;
    }

    public function getReturn(): ?OrderReturn
    {
        return $this->return;
    }

    public function setReturn(?OrderReturn $return): static
    {
        // unset the owning side of the relation if necessary
        if ($return === null && $this->return !== null) {
            $this->return->setPackage(null);
        }

        // set the owning side of the relation if necessary
        if ($return !== null && $return->getPackage() !== $this) {
            $return->setPackage($this);
        }

        $this->return = $return;

        return $this;
    }
}
