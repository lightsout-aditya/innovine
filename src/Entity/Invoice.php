<?php

namespace App\Entity;

use App\Traits\BlameableEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
class Invoice
{
    const INVOICED = 0;
    const SENT = 1;
    const STATUS = [
        self::INVOICED => "Invoiced",
        self::SENT => "Sent",
    ];

    use BlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: SalesOrder::class, inversedBy: 'invoices')]
    private $salesOrder;

    #[ORM\Column(length: 31, unique: true, nullable: true)]
    private $zohoId;

    #[ORM\Column(length: 31, nullable: true)]
    private $invoiceNumber;

    #[ORM\Column(length: 63, nullable: true)]
    private ?string $pdf;

    #[ORM\Column(type: 'float', options: ['default' => '0'])]
    private $total = 0;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $gstInvoice = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $pushed = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $sent = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $downloaded = false;

    #[ORM\Column(type: 'smallint', nullable: false, options: ['default' => self::INVOICED])]
    private $status = self::INVOICED;

    public function getPdfWebPath(): string
    {
        return "/uploads/invoice/{$this->pdf}";
    }

    public function __toString()
    {
        return "{$this->invoiceNumber}";
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

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(?string $invoiceNumber): static
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    public function getPdf(): ?string
    {
        return $this->pdf;
    }

    public function setPdf(?string $pdf): static
    {
        $this->pdf = $pdf;

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

    public function isGstInvoice(): ?bool
    {
        return $this->gstInvoice;
    }

    public function setGstInvoice(bool $gstInvoice): static
    {
        $this->gstInvoice = $gstInvoice;

        return $this;
    }

    public function isPushed(): ?bool
    {
        return $this->pushed;
    }

    public function setPushed(bool $pushed): static
    {
        $this->pushed = $pushed;

        return $this;
    }

    public function isSent(): ?bool
    {
        return $this->sent;
    }

    public function setSent(bool $sent): static
    {
        $this->sent = $sent;

        return $this;
    }

    public function isDownloaded(): ?bool
    {
        return $this->downloaded;
    }

    public function setDownloaded(bool $downloaded): static
    {
        $this->downloaded = $downloaded;

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
}
