<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ItemMandatory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'itemsMandatory')]
    #[ORM\JoinColumn(nullable: false)]
    private $item;

    #[ORM\ManyToOne(targetEntity: ItemGroup::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $group;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 1])]
    private $quantity = 1;

    public function __toString()
    {
        return $this->group;
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

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function getGroup(): ?ItemGroup
    {
        return $this->group;
    }

    public function setGroup(?ItemGroup $group): static
    {
        $this->group = $group;

        return $this;
    }
}
