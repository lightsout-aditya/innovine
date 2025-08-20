<?php
namespace App\Entity;

use App\Traits\BlameableEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
class Cart
{
    use BlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    private $item;

    #[ORM\Column(type: 'integer')]
    private $quantity;

    #[ORM\ManyToOne(targetEntity: Cart::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private $parent = null;

    static function getShippingFees($price): int
    {
        if ($price >= 25000)
            $fee = 0;
        elseif ($price >= 20000)
            $fee = 932;
        elseif ($price >= 15000)
            $fee = 848;
        elseif ($price >= 10000)
            $fee = 763;
        elseif ($price >= 5000)
            $fee = 678;
        elseif ($price >= 1000)
            $fee = 678;
        else
            $fee = 678;
        return $fee;
    }

    public function getTotal(): float|int
    {
        return $this->item->getPrice() * $this->quantity;
    }

    public function getTax($type = 'intra'): array
    {
        $taxes = [];
        foreach ($this->item->getTax($type) as $k => $t) {
            $taxes[$k] = $t * $this->quantity;
        }

        return $taxes;
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }
}
