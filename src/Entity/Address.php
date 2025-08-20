<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Address
{
    const STATES = ['AN'=>'Andaman and Nicobar Islands','AD'=>'Andhra Pradesh','AR'=>'Arunachal Pradesh','AS'=>'Assam','BR'=>'Bihar','CH'=>'Chandigarh','CG'=>'Chhattisgarh','DN'=>'Dadra and Nagar Haveli and Daman and Diu','DL'=>'Delhi','GA'=>'Goa','GJ'=>'Gujarat','HR'=>'Haryana','HP'=>'Himachal Pradesh','JK'=>'Jammu and Kashmir','JH'=>'Jharkhand','KA'=>'Karnataka','KL'=>'Kerala','LA'=>'Ladakh','LD'=>'Lakshadweep','MP'=>'Madhya Pradesh','MH'=>'Maharashtra','MN'=>'Manipur','ML'=>'Meghalaya','MZ'=>'Mizoram','NL'=>'Nagaland','OD'=>'Odisha','PY'=>'Puducherry','PB'=>'Punjab','RJ'=>'Rajasthan','SK'=>'Sikkim','TN'=>'Tamil Nadu','TS'=>'Telangana','TR'=>'Tripura','UP'=>'Uttar Pradesh','UK'=>'Uttarakhand','WB'=>'West Bengal'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(length: 31, nullable: true)]
    private $zohoId;

    #[ORM\Column( nullable: true)]
    private $name;

    #[ORM\Column(type: "text", nullable: true)]
    private $address;

    #[ORM\Column(type: "text", nullable: true)]
    private $street;

    #[ORM\Column(length: "63", nullable: true)]
    private $city;

    #[ORM\Column(length: "63", nullable: true)]
    private $state;

    #[ORM\Column(length: "7", nullable: true)]
    private $stateCode;

    #[ORM\Column(length: "7", nullable: true)]
    private $pincode;

    #[ORM\Column(length: "15", nullable: true)]
    private $phone;

    #[ORM\Column(name: "`default`",type: 'boolean', nullable: false, options: ['default' => false])]
    private $default = false;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'addresses')]
    private $user;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private $active = true;

    # Unmapped - Prevents doctrine lifecycle triggers
    private bool $lifecycleCallback = true;

    public function isLifecycleCallback(): bool
    {
        return $this->lifecycleCallback;
    }

    public function setLifecycleCallback(bool $lifecycleCallback): void
    {
        $this->lifecycleCallback = $lifecycleCallback;
    }

    public function complete(): string
    {
        return trim("{$this->name} {$this->address} {$this->street} {$this->city} {$this->state} {$this->pincode} {$this->phone}");
    }

    public function getTaxType(): string
    {
        $state = $this->state??'Maharashtra';
        return in_array($state, ['Maharashtra', 'MH']) ? 'intra' : 'inter';
    }

    public function getStateCodeGST(): ?string
    {
        switch ($this->stateCode) {
            case 'TG':
                $this->stateCode = 'TS';
                break;
            case 'UT':
                $this->stateCode = 'UK';
                break;
            case 'OR':
                $this->stateCode = 'OD';
                break;
            case 'CT':
                $this->stateCode = 'CG';
                break;
            case 'AP':
                $this->stateCode = 'AD';
                break;
            case 'DH':
                $this->stateCode = 'DN';
                break;
        }

        return $this->stateCode;
    }

    public function __toString()
    {
        return "{$this->name} / {$this->address}, {$this->city}, {$this->state} - {$this->pincode}";
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

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

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): static
    {
        $this->street = $street;

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

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getStateCode(): ?string
    {
        return $this->stateCode;
    }

    public function setStateCode(?string $stateCode): static
    {
        $this->stateCode = $stateCode;

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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function isDefault(): ?bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): static
    {
        $this->default = $default;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}