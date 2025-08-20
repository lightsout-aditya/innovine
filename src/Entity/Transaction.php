<?php

namespace App\Entity;

use App\Traits\BlameableEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
class Transaction
{
    use BlameableEntity;
    use TimestampableEntity;

    const TXN_STATUS = ['created' => 'Created', 'authorized' => 'Authorized', 'captured' => 'Captured', 'refunded' => 'Refunded', 'failed' => 'Failed'];
    const TXT_SUCCESS = 'captured';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\OneToOne(targetEntity: Order::class, inversedBy: 'transaction')]
    private $order;

    #[ORM\Column(type: 'float')]
    private $amount;

    #[ORM\Column(type: 'float', options: ['default' => '0'])]
    private $fee = 0;

    #[ORM\Column(type: 'float', options: ['default' => '0'])]
    private $tax = 0;

    #[ORM\Column(length: 63)]
    private $name;

    #[ORM\Column(length: 127)]
    private $email;

    #[ORM\Column(length: 15)]
    private $mobile;

    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private $shipAddress;

    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private $billAddress;

    #[ORM\Column(type: "text", nullable: true)]
    private $address;

    #[ORM\Column(type: "text", nullable: true)]
    private $address1;

    #[ORM\Column(type: "text", nullable: true)]
    private $state;

    #[ORM\Column(length: "31", nullable: true)]
    private $city;

    #[ORM\Column(length: "7", nullable: true)]
    private $pincode;

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

    #[ORM\Column(length: "10", nullable: true)]
    private $pan;

    #[ORM\Column(length: "31", nullable: true)]
    private $razorpayPaymentId;

    #[ORM\Column(length: "63", nullable: true)]
    private $razorpayOrderId;

    #[ORM\Column(nullable: true)]
    private $razorpaySignature;

    #[ORM\Column(type: "datetime", nullable: true)]
    private $transactionDate;

    #[ORM\Column(length: "31", nullable: true)]
    private $paymentMode;

    #[ORM\Column(length: "7", nullable: true)]
    private $currency;

    #[ORM\Column(type: "json", nullable: true)]
    private $cart;

    #[ORM\Column(type: "json", nullable: true)]
    private $response;

    #[ORM\Column(length: 63, nullable: true)]
    private ?string $attachment;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $offline = false;

    #[ORM\Column(length: "31", nullable: true)]
    private $status;

    public function getAttachmentPath(): string
    {
        return "receipt/$this->attachment";
    }

    public function getAttachmentWebPath(): string
    {
        return "/uploads/{$this->getAttachmentPath()}";
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getFee(): ?float
    {
        return $this->fee;
    }

    public function setFee(float $fee): static
    {
        $this->fee = $fee;

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

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): static
    {
        $this->mobile = $mobile;

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

    public function getAddress1(): ?string
    {
        return $this->address1;
    }

    public function setAddress1(?string $address1): static
    {
        $this->address1 = $address1;

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

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPincode(): ?string
    {
        return $this->pincode;
    }

    public function setPincode(?string $pincode): static
    {
        $this->pincode = $pincode;

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

    public function getPan(): ?string
    {
        return $this->pan;
    }

    public function setPan(?string $pan): static
    {
        $this->pan = $pan;

        return $this;
    }

    public function getRazorpayPaymentId(): ?string
    {
        return $this->razorpayPaymentId;
    }

    public function setRazorpayPaymentId(?string $razorpayPaymentId): static
    {
        $this->razorpayPaymentId = $razorpayPaymentId;

        return $this;
    }

    public function getRazorpayOrderId(): ?string
    {
        return $this->razorpayOrderId;
    }

    public function setRazorpayOrderId(?string $razorpayOrderId): static
    {
        $this->razorpayOrderId = $razorpayOrderId;

        return $this;
    }

    public function getRazorpaySignature(): ?string
    {
        return $this->razorpaySignature;
    }

    public function setRazorpaySignature(?string $razorpaySignature): static
    {
        $this->razorpaySignature = $razorpaySignature;

        return $this;
    }

    public function getTransactionDate(): ?\DateTimeInterface
    {
        return $this->transactionDate;
    }

    public function setTransactionDate(?\DateTimeInterface $transactionDate): static
    {
        $this->transactionDate = $transactionDate;

        return $this;
    }

    public function getPaymentMode(): ?string
    {
        return $this->paymentMode;
    }

    public function setPaymentMode(?string $paymentMode): static
    {
        $this->paymentMode = $paymentMode;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCart(): ?array
    {
        return $this->cart;
    }

    public function setCart(?array $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }

    public function setResponse(?array $response): static
    {
        $this->response = $response;

        return $this;
    }

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment(?string $attachment): static
    {
        $this->attachment = $attachment;

        return $this;
    }

    public function isOffline(): ?bool
    {
        return $this->offline;
    }

    public function setOffline(bool $offline): static
    {
        $this->offline = $offline;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
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
}