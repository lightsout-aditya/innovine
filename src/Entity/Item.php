<?php

namespace App\Entity;

use App\Traits\BlameableEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[UniqueEntity(fields: ['name'], message: 'Item name already exists.')]
class Item
{

    const BOTH = 0;
    const BOYS = 1;
    const GIRLS = 2;
    const GENDER = [
        self::BOYS => "Boys",
        self::GIRLS => "Girls",
        self::BOTH => "Both",
    ];

    use BlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(length: 31, nullable: true)]
    private $zohoId;

    // --- NEW FIELD START ---
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $itemCode = null;
    // --- NEW FIELD END ---

    #[ORM\ManyToOne(targetEntity: ItemCategory::class)]
    private $category;

    #[ORM\ManyToOne(targetEntity: ItemGroup::class, inversedBy: 'items')]
    private $group;

    #[ORM\ManyToOne(targetEntity: Grade::class)]
    private $grade;

    #[ORM\Column(nullable: false)]
    private $name;

    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(unique: true)]
    private $slug;

    #[ORM\Column(unique: false, nullable: true)]
    private $sku;

    #[ORM\Column(length: 15, unique: false, nullable: true)]
    private $hsnCode;

    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $size = 0;

    #[ORM\Column(length: 15, nullable: true)]
    private $unit;

    #[ORM\Column(type: 'float', nullable: true)]
    private $rate;

    #[ORM\Column(type: 'float', nullable: true)]
    private $purchaseRate;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private $comboProduct = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private $taxable = true;

    #[ORM\ManyToOne(targetEntity: Tax::class)]
    private $intraTax;

    #[ORM\ManyToOne(targetEntity: Tax::class)]
    private $interTax;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $stockOnHand = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $availableStock = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $actualAvailableStock = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $committedStock = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $actualCommittedStock = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $availableForSaleStock = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $actualAvailableForSaleStock = 0;

    #[ORM\OneToMany(mappedBy: 'compositeItem', targetEntity: CompositeItem::class, cascade: ["persist"], orphanRemoval: true)]
    private $compositeItems;

    #[ORM\OneToMany(mappedBy: 'item', targetEntity: ItemStock::class, cascade: ["persist"], orphanRemoval: true)]
    private $stocks;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private $active = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private $imported = false;

    #[ORM\Column(length: 127, nullable: true)]
    #[Assert\Length(max: 127)]
    private $metaTitle;

    #[ORM\Column(type: "text", nullable: true)]
    private $metaDescription;

    #[ORM\Column(length: 511, nullable: true)]
    #[Assert\Length(max: 511)]
    private $metaKeywords;

    #[ORM\OneToMany(mappedBy: 'item', targetEntity: ItemImage::class, cascade: ["persist"], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private $images;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $featured = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $trending = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $showInPortal = true;

    #[ORM\Column(type: 'smallint', nullable: false, options: ['default' => self::BOTH])]
    private int $gender = self::BOTH;

    #[ORM\ManyToMany(targetEntity: School::class, mappedBy: 'items', cascade: ['persist'])]
    private $schools;

    #[ORM\OneToMany(targetEntity: ItemMandatory::class, mappedBy: 'item', cascade: ["persist"], orphanRemoval: true)]
    private $itemsMandatory;

    public function __construct()
    {
        $this->compositeItems = new ArrayCollection();
        $this->stocks = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->schools = new ArrayCollection();
        $this->itemsMandatory = new ArrayCollection();
    }

    public function getPrice(): float|int
    {
        $price = $this->rate;
        if($this->comboProduct) {
            foreach ($this->compositeItems as $compositeItem) {
                $price += $compositeItem->getItem()->getRate() * $compositeItem->getQuantity();
            }
        }
        return floatval($price);
    }

    public function getGroupImages()
    {
        $images = $this->images;
        if(!count($images) and $group = $this->group and $item = $group->getActiveItems()?->first()) {
            $images = $item->getImages();
        }
        return $images;
    }

    public function getImage()
    {
        return count($this->getGroupImages()) ? $this->getGroupImages()->first()->getImage() : null;
    }

    public function getTax($type = 'intra'): array
    {
        $tax = [];
        $method = $type == 'intra' ? 'getIntraTax' : 'getInterTax';
        if($this->comboProduct) {
            foreach ($this->compositeItems as $compositeItem) {
                $item = $compositeItem->getItem();
                $taxType = $item->$method();
                $tax[$taxType->getName()] = ($tax[$taxType->getName()]??0) + ($item->getRate() * $compositeItem->getQuantity() * $taxType->getPercent() / 100);
            }
        }else{
            $tax[$this->$method()->getName()] = $this->getRate() * $this->$method()->getPercent() / 100;
        }
        return $tax;
    }

    public function inStock($quantity = 1, $carts = null): bool
    {
        if($this->comboProduct) {
            $stock = true;
            foreach ($this->compositeItems as $compositeItem) {
                $stock = $compositeItem->getItem()->getActualAvailableForSaleStock() >= ($carts ? $this->getTotalQuantity($compositeItem->getItem(), $carts) : ($compositeItem->getQuantity() * $quantity));
                if(!$stock){ $stock = false; break; }
            }
        }else{
            $stock = $this->actualAvailableForSaleStock >= ($carts ? $this->getTotalQuantity($this, $carts) : $quantity);
        }
        return $stock;
    }

    public function getTotalQuantity($item, $carts){
        $quantity = 0;
        foreach ($carts as $cart) {
            $cartItem = $cart->getItem();
            if (!$cartItem->isComboProduct() && $cartItem == $item) {
                $quantity += $cart->getQuantity();
            } elseif ($cartItem->isComboProduct()) {
                foreach ($cartItem->getCompositeItems() as $ci) {
                    if ($ci->getItem() == $item) {
                        $quantity += $ci->getQuantity() * $cart->getQuantity();
                    }
                }
            }
        }
        return $quantity;
    }

    public function searchWarehouse($quantity)
    {
        $warehouse = null;
        if(count($this->stocks)) {
            foreach ($this->stocks as $stock) {
                if ($stock->getActualAvailableForSaleStock() >= $quantity) {
                    $warehouse = $stock->getWarehouse();
                    break;
                }
            }
        }
        return $warehouse;
    }

    public function __toString()
    {
        return "{$this->name}";
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

    // --- NEW GETTER & SETTER ---
    public function getItemCode(): ?string
    {
        return $this->itemCode;
    }

    public function setItemCode(?string $itemCode): static
    {
        $this->itemCode = $itemCode;
        return $this;
    }
    // ---------------------------

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

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getHsnCode(): ?string
    {
        return $this->hsnCode;
    }

    public function setHsnCode(?string $hsnCode): static
    {
        $this->hsnCode = $hsnCode;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(?float $rate): static
    {
        $this->rate = $rate;

        return $this;
    }

    public function getPurchaseRate(): ?float
    {
        return $this->purchaseRate;
    }

    public function setPurchaseRate(?float $purchaseRate): static
    {
        $this->purchaseRate = $purchaseRate;

        return $this;
    }

    public function isComboProduct(): ?bool
    {
        return $this->comboProduct;
    }

    public function setComboProduct(bool $comboProduct): static
    {
        $this->comboProduct = $comboProduct;

        return $this;
    }

    public function isTaxable(): ?bool
    {
        return $this->taxable;
    }

    public function setTaxable(bool $taxable): static
    {
        $this->taxable = $taxable;

        return $this;
    }

    public function getStockOnHand(): ?int
    {
        return $this->stockOnHand;
    }

    public function setStockOnHand(int $stockOnHand): static
    {
        $this->stockOnHand = $stockOnHand;

        return $this;
    }

    public function getAvailableStock(): ?int
    {
        return $this->availableStock;
    }

    public function setAvailableStock(int $availableStock): static
    {
        $this->availableStock = $availableStock;

        return $this;
    }

    public function getActualAvailableStock(): ?int
    {
        return $this->actualAvailableStock;
    }

    public function setActualAvailableStock(int $actualAvailableStock): static
    {
        $this->actualAvailableStock = $actualAvailableStock;

        return $this;
    }

    public function getCommittedStock(): ?int
    {
        return $this->committedStock;
    }

    public function setCommittedStock(int $committedStock): static
    {
        $this->committedStock = $committedStock;

        return $this;
    }

    public function getActualCommittedStock(): ?int
    {
        return $this->actualCommittedStock;
    }

    public function setActualCommittedStock(int $actualCommittedStock): static
    {
        $this->actualCommittedStock = $actualCommittedStock;

        return $this;
    }

    public function getAvailableForSaleStock(): ?int
    {
        return $this->availableForSaleStock;
    }

    public function setAvailableForSaleStock(int $availableForSaleStock): static
    {
        $this->availableForSaleStock = $availableForSaleStock;

        return $this;
    }

    public function getActualAvailableForSaleStock(): ?int
    {
        return $this->actualAvailableForSaleStock;
    }

    public function setActualAvailableForSaleStock(int $actualAvailableForSaleStock): static
    {
        $this->actualAvailableForSaleStock = $actualAvailableForSaleStock;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function isImported(): ?bool
    {
        return $this->imported;
    }

    public function setImported(bool $imported): static
    {
        $this->imported = $imported;

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

    public function isFeatured(): ?bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): static
    {
        $this->featured = $featured;

        return $this;
    }

    public function isTrending(): ?bool
    {
        return $this->trending;
    }

    public function setTrending(bool $trending): static
    {
        $this->trending = $trending;

        return $this;
    }

    public function isShowInPortal(): ?bool
    {
        return $this->showInPortal;
    }

    public function setShowInPortal(bool $showInPortal): static
    {
        $this->showInPortal = $showInPortal;

        return $this;
    }

    public function getGender(): ?int
    {
        return $this->gender;
    }

    public function setGender(int $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getCategory(): ?ItemCategory
    {
        return $this->category;
    }

    public function setCategory(?ItemCategory $category): static
    {
        $this->category = $category;

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

    public function getGrade(): ?Grade
    {
        return $this->grade;
    }

    public function setGrade(?Grade $grade): static
    {
        $this->grade = $grade;

        return $this;
    }

    public function getIntraTax(): ?Tax
    {
        return $this->intraTax;
    }

    public function setIntraTax(?Tax $intraTax): static
    {
        $this->intraTax = $intraTax;

        return $this;
    }

    public function getInterTax(): ?Tax
    {
        return $this->interTax;
    }

    public function setInterTax(?Tax $interTax): static
    {
        $this->interTax = $interTax;

        return $this;
    }

    /**
     * @return Collection<int, CompositeItem>
     */
    public function getCompositeItems(): Collection
    {
        return $this->compositeItems;
    }

    public function addCompositeItem(CompositeItem $compositeItem): static
    {
        if (!$this->compositeItems->contains($compositeItem)) {
            $this->compositeItems->add($compositeItem);
            $compositeItem->setCompositeItem($this);
        }

        return $this;
    }

    public function removeCompositeItem(CompositeItem $compositeItem): static
    {
        if ($this->compositeItems->removeElement($compositeItem)) {
            // set the owning side to null (unless already changed)
            if ($compositeItem->getCompositeItem() === $this) {
                $compositeItem->setCompositeItem(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ItemStock>
     */
    public function getStocks(): Collection
    {
        return $this->stocks;
    }

    public function addStock(ItemStock $stock): static
    {
        if (!$this->stocks->contains($stock)) {
            $this->stocks->add($stock);
            $stock->setItem($this);
        }

        return $this;
    }

    public function removeStock(ItemStock $stock): static
    {
        if ($this->stocks->removeElement($stock)) {
            // set the owning side to null (unless already changed)
            if ($stock->getItem() === $this) {
                $stock->setItem(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ItemImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(ItemImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setItem($this);
        }

        return $this;
    }

    public function removeImage(ItemImage $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getItem() === $this) {
                $image->setItem(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, School>
     */
    public function getSchools(): Collection
    {
        return $this->schools;
    }

    public function addSchool(School $school): static
    {
        if (!$this->schools->contains($school)) {
            $this->schools->add($school);
            $school->addItem($this);
        }

        return $this;
    }

    public function removeSchool(School $school): static
    {
        if ($this->schools->removeElement($school)) {
            $school->removeItem($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, ItemMandatory>
     */
    public function getItemsMandatory(): Collection
    {
        return $this->itemsMandatory;
    }

    public function addItemsMandatory(ItemMandatory $itemsMandatory): static
    {
        if (!$this->itemsMandatory->contains($itemsMandatory)) {
            $this->itemsMandatory->add($itemsMandatory);
            $itemsMandatory->setItem($this);
        }

        return $this;
    }

    public function removeItemsMandatory(ItemMandatory $itemsMandatory): static
    {
        if ($this->itemsMandatory->removeElement($itemsMandatory)) {
            // set the owning side to null (unless already changed)
            if ($itemsMandatory->getItem() === $this) {
                $itemsMandatory->setItem(null);
            }
        }

        return $this;
    }
}