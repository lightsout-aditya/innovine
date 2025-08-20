<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(length: 63, nullable: true)]
    private $logo;

    #[ORM\Column(type: 'text', nullable: true)]
    private $tagline;

    #[ORM\Column(length: 63, nullable: true)]
    private $logoFooter;

    #[ORM\Column(type: 'text', nullable: true)]
    private $footerDescription;

    #[ORM\Column(nullable: true)]
    private $facebookLink;

    #[ORM\Column(nullable: true)]
    private $xLink;

    #[ORM\Column(nullable: true)]
    private $instagramLink;

    #[ORM\Column(nullable: true)]
    private $linkedinLink;

    #[ORM\Column(nullable: true)]
    private $whatsappLink;

    #[ORM\Column(type: 'text', nullable: true)]
    private $contactPhone;

    #[ORM\Column(type: 'text', nullable: true)]
    private $contactEmail;

    #[ORM\Column(type: 'text', nullable: true)]
    private $address;

    #[ORM\Column(nullable: true)]
    private $mapLink;

    #[ORM\Column(type: 'text', nullable: true)]
    private $homeBannerTitle;

    #[ORM\Column(type: 'text', nullable: true)]
    private $homeBannerDescription;

    #[ORM\Column(length: 63, nullable: true)]
    private $homeBannerImage;

    #[ORM\Column(type: 'text', nullable: true)]
    private $trendingProductsTitle;

    #[ORM\Column(type: 'text', nullable: true)]
    private $trendingProductsDescription;

    #[ORM\Column(type: 'text', nullable: true)]
    private $combinedKitTitle;

    #[ORM\Column(type: 'text', nullable: true)]
    private $combinedKitDescription;

    #[ORM\Column(type: 'text', nullable: true)]
    private $howWorksTitle;

    #[ORM\Column(type: 'text', nullable: true)]
    private $howWorksDescription;

    #[ORM\OneToMany(mappedBy: 'setting', targetEntity: KeyFeature::class, cascade: ["persist"], orphanRemoval: true)]
    private $keyFeatures;

    #[ORM\OneToMany(mappedBy: 'setting', targetEntity: HowWorks::class, cascade: ["persist"], orphanRemoval: true)]
    private $howWorks;

    #[ORM\Column(length: 63, nullable: true)]
    private $contactUsImage;

    #[ORM\Column(type: 'text', nullable: true)]
    private $contactUsTitle;

    #[ORM\Column(type: 'text', nullable: true)]
    private $contactUsDescription;

    #[ORM\Column(type: 'text', nullable: true)]
    private $contactUsMap;

    #[ORM\Column(length: 31, nullable: true)]
    #[Assert\Length(max: 31)]
    private $googleAnalyticsId;

    #[ORM\Column(length: 31, nullable: true)]
    #[Assert\Length(max: 31)]
    private $googleTagManagerCode;

    #[ORM\Column(length: 31, nullable: true)]
    #[Assert\Length(max: 31)]
    private $googleSiteVerificationCode;

    #[ORM\Column(length: 127, nullable: true)]
    private $zohoToken;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $zohoTokenCreatedAt;

    #[ORM\Column(type: "text", nullable: true)]
    private $safexToken;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $safexTokenCreatedAt;

    #[ORM\Column(type: "text", nullable: true)]
    private $blueDartToken;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $blueDartTokenCreatedAt;

    #[ORM\Column(type: "json", nullable: true)]
    private $invoiceCc;

    #[ORM\Column(length: 127, nullable: true)]
    #[Assert\Length(max: 127)]
    private $metaTitle;

    #[ORM\Column(type: "text", nullable: true)]
    private $metaDescription;

    #[ORM\Column(length: 511, nullable: true)]
    #[Assert\Length(max: 511)]
    private $metaKeywords;

    public function __construct()
    {
        $this->keyFeatures = new ArrayCollection();
        $this->howWorks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function getTagline(): ?string
    {
        return $this->tagline;
    }

    public function setTagline(?string $tagline): static
    {
        $this->tagline = $tagline;

        return $this;
    }

    public function getLogoFooter(): ?string
    {
        return $this->logoFooter;
    }

    public function setLogoFooter(?string $logoFooter): static
    {
        $this->logoFooter = $logoFooter;

        return $this;
    }

    public function getFooterDescription(): ?string
    {
        return $this->footerDescription;
    }

    public function setFooterDescription(?string $footerDescription): static
    {
        $this->footerDescription = $footerDescription;

        return $this;
    }

    public function getFacebookLink(): ?string
    {
        return $this->facebookLink;
    }

    public function setFacebookLink(?string $facebookLink): static
    {
        $this->facebookLink = $facebookLink;

        return $this;
    }

    public function getXLink(): ?string
    {
        return $this->xLink;
    }

    public function setXLink(?string $xLink): static
    {
        $this->xLink = $xLink;

        return $this;
    }

    public function getInstagramLink(): ?string
    {
        return $this->instagramLink;
    }

    public function setInstagramLink(?string $instagramLink): static
    {
        $this->instagramLink = $instagramLink;

        return $this;
    }

    public function getLinkedinLink(): ?string
    {
        return $this->linkedinLink;
    }

    public function setLinkedinLink(?string $linkedinLink): static
    {
        $this->linkedinLink = $linkedinLink;

        return $this;
    }

    public function getWhatsappLink(): ?string
    {
        return $this->whatsappLink;
    }

    public function setWhatsappLink(?string $whatsappLink): static
    {
        $this->whatsappLink = $whatsappLink;

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): static
    {
        $this->contactPhone = $contactPhone;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;

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

    public function getMapLink(): ?string
    {
        return $this->mapLink;
    }

    public function setMapLink(?string $mapLink): static
    {
        $this->mapLink = $mapLink;

        return $this;
    }

    public function getHomeBannerTitle(): ?string
    {
        return $this->homeBannerTitle;
    }

    public function setHomeBannerTitle(?string $homeBannerTitle): static
    {
        $this->homeBannerTitle = $homeBannerTitle;

        return $this;
    }

    public function getHomeBannerDescription(): ?string
    {
        return $this->homeBannerDescription;
    }

    public function setHomeBannerDescription(?string $homeBannerDescription): static
    {
        $this->homeBannerDescription = $homeBannerDescription;

        return $this;
    }

    public function getHomeBannerImage(): ?string
    {
        return $this->homeBannerImage;
    }

    public function setHomeBannerImage(?string $homeBannerImage): static
    {
        $this->homeBannerImage = $homeBannerImage;

        return $this;
    }

    public function getTrendingProductsTitle(): ?string
    {
        return $this->trendingProductsTitle;
    }

    public function setTrendingProductsTitle(?string $trendingProductsTitle): static
    {
        $this->trendingProductsTitle = $trendingProductsTitle;

        return $this;
    }

    public function getTrendingProductsDescription(): ?string
    {
        return $this->trendingProductsDescription;
    }

    public function setTrendingProductsDescription(?string $trendingProductsDescription): static
    {
        $this->trendingProductsDescription = $trendingProductsDescription;

        return $this;
    }

    public function getCombinedKitTitle(): ?string
    {
        return $this->combinedKitTitle;
    }

    public function setCombinedKitTitle(?string $combinedKitTitle): static
    {
        $this->combinedKitTitle = $combinedKitTitle;

        return $this;
    }

    public function getCombinedKitDescription(): ?string
    {
        return $this->combinedKitDescription;
    }

    public function setCombinedKitDescription(?string $combinedKitDescription): static
    {
        $this->combinedKitDescription = $combinedKitDescription;

        return $this;
    }

    public function getHowWorksTitle(): ?string
    {
        return $this->howWorksTitle;
    }

    public function setHowWorksTitle(?string $howWorksTitle): static
    {
        $this->howWorksTitle = $howWorksTitle;

        return $this;
    }

    public function getHowWorksDescription(): ?string
    {
        return $this->howWorksDescription;
    }

    public function setHowWorksDescription(?string $howWorksDescription): static
    {
        $this->howWorksDescription = $howWorksDescription;

        return $this;
    }

    public function getContactUsImage(): ?string
    {
        return $this->contactUsImage;
    }

    public function setContactUsImage(?string $contactUsImage): static
    {
        $this->contactUsImage = $contactUsImage;

        return $this;
    }

    public function getContactUsTitle(): ?string
    {
        return $this->contactUsTitle;
    }

    public function setContactUsTitle(?string $contactUsTitle): static
    {
        $this->contactUsTitle = $contactUsTitle;

        return $this;
    }

    public function getContactUsDescription(): ?string
    {
        return $this->contactUsDescription;
    }

    public function setContactUsDescription(?string $contactUsDescription): static
    {
        $this->contactUsDescription = $contactUsDescription;

        return $this;
    }

    public function getContactUsMap(): ?string
    {
        return $this->contactUsMap;
    }

    public function setContactUsMap(?string $contactUsMap): static
    {
        $this->contactUsMap = $contactUsMap;

        return $this;
    }

    public function getGoogleAnalyticsId(): ?string
    {
        return $this->googleAnalyticsId;
    }

    public function setGoogleAnalyticsId(?string $googleAnalyticsId): static
    {
        $this->googleAnalyticsId = $googleAnalyticsId;

        return $this;
    }

    public function getGoogleTagManagerCode(): ?string
    {
        return $this->googleTagManagerCode;
    }

    public function setGoogleTagManagerCode(?string $googleTagManagerCode): static
    {
        $this->googleTagManagerCode = $googleTagManagerCode;

        return $this;
    }

    public function getGoogleSiteVerificationCode(): ?string
    {
        return $this->googleSiteVerificationCode;
    }

    public function setGoogleSiteVerificationCode(?string $googleSiteVerificationCode): static
    {
        $this->googleSiteVerificationCode = $googleSiteVerificationCode;

        return $this;
    }

    public function getZohoToken(): ?string
    {
        return $this->zohoToken;
    }

    public function setZohoToken(?string $zohoToken): static
    {
        $this->zohoToken = $zohoToken;

        return $this;
    }

    public function getZohoTokenCreatedAt(): ?\DateTimeInterface
    {
        return $this->zohoTokenCreatedAt;
    }

    public function setZohoTokenCreatedAt(?\DateTimeInterface $zohoTokenCreatedAt): static
    {
        $this->zohoTokenCreatedAt = $zohoTokenCreatedAt;

        return $this;
    }

    public function getSafexToken(): ?string
    {
        return $this->safexToken;
    }

    public function setSafexToken(?string $safexToken): static
    {
        $this->safexToken = $safexToken;

        return $this;
    }

    public function getSafexTokenCreatedAt(): ?\DateTimeInterface
    {
        return $this->safexTokenCreatedAt;
    }

    public function setSafexTokenCreatedAt(?\DateTimeInterface $safexTokenCreatedAt): static
    {
        $this->safexTokenCreatedAt = $safexTokenCreatedAt;

        return $this;
    }

    public function getBlueDartToken(): ?string
    {
        return $this->blueDartToken;
    }

    public function setBlueDartToken(?string $blueDartToken): static
    {
        $this->blueDartToken = $blueDartToken;

        return $this;
    }

    public function getBlueDartTokenCreatedAt(): ?\DateTimeInterface
    {
        return $this->blueDartTokenCreatedAt;
    }

    public function setBlueDartTokenCreatedAt(?\DateTimeInterface $blueDartTokenCreatedAt): static
    {
        $this->blueDartTokenCreatedAt = $blueDartTokenCreatedAt;

        return $this;
    }

    public function getInvoiceCc(): ?array
    {
        return $this->invoiceCc;
    }

    public function setInvoiceCc(?array $invoiceCc): static
    {
        $this->invoiceCc = $invoiceCc;

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
     * @return Collection<int, KeyFeature>
     */
    public function getKeyFeatures(): Collection
    {
        return $this->keyFeatures;
    }

    public function addKeyFeature(KeyFeature $keyFeature): static
    {
        if (!$this->keyFeatures->contains($keyFeature)) {
            $this->keyFeatures->add($keyFeature);
            $keyFeature->setSetting($this);
        }

        return $this;
    }

    public function removeKeyFeature(KeyFeature $keyFeature): static
    {
        if ($this->keyFeatures->removeElement($keyFeature)) {
            // set the owning side to null (unless already changed)
            if ($keyFeature->getSetting() === $this) {
                $keyFeature->setSetting(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, HowWorks>
     */
    public function getHowWorks(): Collection
    {
        return $this->howWorks;
    }

    public function addHowWork(HowWorks $howWork): static
    {
        if (!$this->howWorks->contains($howWork)) {
            $this->howWorks->add($howWork);
            $howWork->setSetting($this);
        }

        return $this;
    }

    public function removeHowWork(HowWorks $howWork): static
    {
        if ($this->howWorks->removeElement($howWork)) {
            // set the owning side to null (unless already changed)
            if ($howWork->getSetting() === $this) {
                $howWork->setSetting(null);
            }
        }

        return $this;
    }
}
