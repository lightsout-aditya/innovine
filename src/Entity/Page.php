<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[UniqueEntity(fields: ['name'], message: 'This name is already in use.')]
class Page
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(length: 31, unique: true)]
    private ?string $name;

    #[Gedmo\Slug(fields: ['name'], updatable: true)]
    #[ORM\Column(length: 63, unique: true)]
    private ?string $slug;

    #[ORM\Column(type: 'text',nullable: true)]
    private ?string $title;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $body;

    #[ORM\Column(length: 63, nullable: true)]
    private $featuredImage;


    #[ORM\OneToMany(mappedBy: 'page', targetEntity: PageSection::class, cascade: ["persist"], orphanRemoval: true)]
    private $sections;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private $footerNav = false;

    #[ORM\Column(name: "`position`", type: 'smallint', nullable: true)]
    private $position = 0;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $publish = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private $public = true;

    #[ORM\Column(length: 127, nullable: true)]
    #[Assert\Length(max: 127)]
    private ?string $metaTitle;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $metaDescription;

    #[ORM\Column(length: 511, nullable: true)]
    #[Assert\Length(max: 511)]
    private ?string $metaKeywords;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getFeaturedImage(): ?string
    {
        return $this->featuredImage;
    }

    public function setFeaturedImage(?string $featuredImage): static
    {
        $this->featuredImage = $featuredImage;

        return $this;
    }

    public function isFooterNav(): ?bool
    {
        return $this->footerNav;
    }

    public function setFooterNav(bool $footerNav): static
    {
        $this->footerNav = $footerNav;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function isPublish(): ?bool
    {
        return $this->publish;
    }

    public function setPublish(bool $publish): static
    {
        $this->publish = $publish;

        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getMetaKeywords(): ?string
    {
        return $this->metaKeywords;
    }

    public function setMetaKeywords(?string $metaKeywords): static
    {
        $this->metaKeywords = $metaKeywords;

        return $this;
    }

    /**
     * @return Collection<int, PageSection>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function addSection(PageSection $section): static
    {
        if (!$this->sections->contains($section)) {
            $this->sections->add($section);
            $section->setPage($this);
        }

        return $this;
    }

    public function removeSection(PageSection $section): static
    {
        if ($this->sections->removeElement($section)) {
            // set the owning side to null (unless already changed)
            if ($section->getPage() === $this) {
                $section->setPage(null);
            }
        }

        return $this;
    }
}
