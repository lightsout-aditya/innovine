<?php

namespace App\Entity;

use App\Traits\BlameableEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
class OrderReturn
{
    const NEW = 1;
    const RESOLVED = 2;
    const STATUS = [
        self::NEW => "New",
        self::RESOLVED => "Resolved",
    ];

    use BlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\OneToOne(inversedBy: 'return', targetEntity: Package::class)]
    private $package;

    #[ORM\Column(nullable: true)]
    private $subject;

    #[ORM\Column(length: 63, nullable: true)]
    private ?string $attachment;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message;

    #[ORM\Column(type: 'smallint', nullable: false, options: ['default' => self::NEW])]
    private $status = self::NEW;

    public function __toString()
    {
        return "{$this->package}";
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

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

    public function getPackage(): ?Package
    {
        return $this->package;
    }

    public function setPackage(?Package $package): static
    {
        $this->package = $package;

        return $this;
    }
}
