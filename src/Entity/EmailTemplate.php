<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[UniqueEntity(fields: ['name'], message: 'This name is already in use.')]
class EmailTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(length: 63, unique: true)]
    private $name;

    #[Gedmo\Slug(fields: ['name'], updatable: true)]
    #[ORM\Column(length: 63, unique: true)]
    private $slug;

    #[ORM\Column(length: 255)]
    private $subject;

    #[ORM\Column(name: '`from`', length: 63, nullable: true)]
    private $from;

    #[ORM\Column(length: 63, nullable: true)]
    private $fromName;

    #[ORM\Column(name: '`to`', type: "json", nullable: true)]
    private $to;

    #[ORM\Column(type: "json", nullable: true)]
    private $cc;

    #[ORM\Column(type: "json", nullable: true)]
    private $bcc;

    #[ORM\Column(type: "text", nullable: false)]
    private $body;

    public function getFormattedBody($params = []): ?string
    {
        $body = $this->body;
        foreach ($params as $k => $v) {
            $body = str_replace("{{$k}}", $v, $body);
        }
        return $body;
    }

    public function getFormattedSubject($params = []): ?string
    {
        $subject = $this->subject;
        foreach ($params as $k => $v) {
            $subject = str_replace("{{$k}}", $v, $subject);
        }
        return $subject;
    }

    public function __toString()
    {
        return "{$this->name}";
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getFrom(): ?string
    {
        return $this->from;
    }

    public function setFrom(?string $from): static
    {
        $this->from = $from;

        return $this;
    }

    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    public function setFromName(?string $fromName): static
    {
        $this->fromName = $fromName;

        return $this;
    }

    public function getTo(): ?array
    {
        return $this->to;
    }

    public function setTo(?array $to): static
    {
        $this->to = $to;

        return $this;
    }

    public function getCc(): ?array
    {
        return $this->cc;
    }

    public function setCc(?array $cc): static
    {
        $this->cc = $cc;

        return $this;
    }

    public function getBcc(): ?array
    {
        return $this->bcc;
    }

    public function setBcc(?array $bcc): static
    {
        $this->bcc = $bcc;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }
}
