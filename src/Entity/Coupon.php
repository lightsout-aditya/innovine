<?php

namespace App\Entity;

use App\Traits\CreatedByEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[UniqueEntity(fields: ['code'], message: 'Coupon code already exists')]
class Coupon
{
    use CreatedByEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(name: "`code`", length: 31, unique: true)]
    private string $code;

    #[ORM\Column(type: 'float')]
    #[Assert\GreaterThan(0)]
    #[Assert\LessThanOrEqual(100)]
    private float $discountPercent;

    #[ORM\Column(nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(name: "`usage`", type: 'integer', nullable: false, options: ['default' => 0])]
    private int $usage = 0;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 1])]
    private int $maxLimit = 1;

    #[ORM\Column(nullable: true)]
    private ?string $allowedEmail = null;

    #[ORM\Column(nullable: true)]
    private ?string $allowedMobile = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $active = true;

    #[ORM\OneToMany(targetEntity: CouponLog::class, mappedBy: 'coupon')]
    private $logs;

    // Unmapped property
    private int $maxDiscount = 50;

    public function __construct()
    {
        $this->logs = new ArrayCollection();
    }

    public function getMaxDiscount(): int
    {
        return $this->maxDiscount;
    }

    public function setMaxDiscount(int $maxDiscount): void
    {
        $this->maxDiscount = $maxDiscount;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        if ($this->getMaxDiscount() && $this->getDiscountPercent() > $this->getMaxDiscount()) {
            $context->buildViolation("Max upto {$this->getMaxDiscount()}% discount allowed.")
                ->atPath('discountPercent')
                ->addViolation();
        }
    }

    public function __toString(): string
    {
        return $this->code;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getDiscountPercent(): ?float
    {
        return $this->discountPercent;
    }

    public function setDiscountPercent(float $discountPercent): static
    {
        $this->discountPercent = $discountPercent;

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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getUsage(): ?int
    {
        return $this->usage;
    }

    public function setUsage(int $usage): static
    {
        $this->usage = $usage;

        return $this;
    }

    public function getMaxLimit(): ?int
    {
        return $this->maxLimit;
    }

    public function setMaxLimit(int $maxLimit): static
    {
        $this->maxLimit = $maxLimit;

        return $this;
    }

    public function getAllowedEmail(): ?string
    {
        return $this->allowedEmail;
    }

    public function setAllowedEmail(?string $allowedEmail): static
    {
        $this->allowedEmail = $allowedEmail;

        return $this;
    }

    public function getAllowedMobile(): ?string
    {
        return $this->allowedMobile;
    }

    public function setAllowedMobile(?string $allowedMobile): static
    {
        $this->allowedMobile = $allowedMobile;

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

    /**
     * @return Collection<int, CouponLog>
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(CouponLog $log): static
    {
        if (!$this->logs->contains($log)) {
            $this->logs->add($log);
            $log->setCoupon($this);
        }

        return $this;
    }

    public function removeLog(CouponLog $log): static
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getCoupon() === $this) {
                $log->setCoupon(null);
            }
        }

        return $this;
    }
}
