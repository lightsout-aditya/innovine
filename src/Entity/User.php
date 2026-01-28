<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\School;


#[UniqueEntity(fields: ['email'], message: 'This email is already in use.')]
#[UniqueEntity(fields: ['mobile'], message: 'This mobile is already in use.')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    const PASSWORD_POLICY_REGEX = "/^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9!@#$%^&*()_]+){8,}$/";
    const PASSWORD_POLICY_MESSAGE= "Password must be at least 8 characters long, contain at least one number and have a mixture of uppercase and lowercase letters and special character (!,@,#,$,* etc.).";
    const GENDERS = [1 => 'Male', 2 => 'Female'];
    const ROLES = [
        'ROLE_USER' => 'USER',
        'ROLE_SALES' => 'SALES',
        'ROLE_ADMIN' => 'ADMIN',
        'ROLE_SUPER_ADMIN' => 'SUPER ADMIN',
    ];

    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 127, unique: true)]
    private $email;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\Column(nullable: true)]
    private $password;

    #[Assert\Regex(
        pattern: self::PASSWORD_POLICY_REGEX,
        message: self::PASSWORD_POLICY_MESSAGE
    )]
    private $plainPassword;

    #[ORM\Column(length: 127, nullable: true)]
    private $firstName;

    #[ORM\Column(length: 31, nullable: true)]
    private $lastName;

    #[ORM\Column(length: 10, nullable: true)]
    private $mobile;

    #[ORM\Column(length: 63, nullable: true)]
    private $avatar;

    #[ORM\Column(length: 127, nullable: true)]
    private $token;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $tokenDate;

    #[ORM\Column(type: 'datetime',  nullable: true)]
    private $lastLogin;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private $emailVerified = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private $mobileVerified = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private $enabled = false;

    #[ORM\Column(type: "date", length: 31, nullable: true)]
    private $dateOfBirth;

    #[ORM\Column(length: "31", nullable: true)]
    private $gender;

    #[ORM\Column(length: 31, nullable: true)]
    private $zohoId;

    #[ORM\Column(nullable: true)]
    private $displayName;

    #[ORM\Column(length: 127, nullable: true)]
    private $company;

    #[ORM\Column(length: 127, nullable: true)]
    private $location;

    #[ORM\Column(length: 31, nullable: true)]
    private $type;

    #[ORM\Column(length: 63, nullable: true)]
    private $phone;

    #[ORM\Column(length: 15, nullable: true)]
    private $pan;

    #[ORM\Column(length: 31, nullable: true)]
    private $gstNumber;

    #[ORM\Column(length: 31, nullable: true)]
    private $gstTreatment;

    #[ORM\Column(length: 15, nullable: true)]
    private $placeOfContact;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private $imported = false;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Address::class, cascade: ["persist"], orphanRemoval: true)]
    #[ORM\OrderBy(['default' => 'DESC', 'id' => 'DESC'])]
    private $addresses;

    #[ORM\Column(length: 512, nullable: true)]
    private $additionalEmail;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private $offlinePayment = false;

    #[ORM\ManyToOne(targetEntity: School::class)]
#[ORM\JoinColumn(nullable: true)]
private ?School $school = null;


    # Unmapped
    private $sendEmail = true;

    # Unmapped - Prevents doctrine lifecycle triggers
    private bool $lifecycleCallback = true;

    public function isLifecycleCallback(): bool
    {
        return $this->lifecycleCallback;
    }

    public function isSendEmail(): bool
    {
        return $this->sendEmail;
    }

    public function setSendEmail(bool $sendEmail): void
    {
        $this->sendEmail = $sendEmail;
    }

    public function setLifecycleCallback(bool $lifecycleCallback): void
    {
        $this->lifecycleCallback = $lifecycleCallback;
    }

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }

    public function role(): string
    {
        if($this->isSuperAdmin()){
            return 'Super Admin';
        }elseif($this->isAdmin()){
            return 'Admin';
        }elseif($this->isSales()){
            return 'Sales';
        }else{
            return 'User';
        }
    }

    public function getRole()
    {
        return count($this->getRoles()) ? $this->getRoles()[0] : null;
    }

    public function setRole($role)
    {
        $this->setRoles([$role]);
    }

    public function isSuperAdmin(): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $this->roles);
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles) || in_array('ROLE_SUPER_ADMIN', $this->roles);
    }

    public function isSales(): bool
    {
        return in_array('ROLE_SALES', $this->roles);
    }

    public function getName(): string
    {
        return ucwords(strtolower("{$this->firstName} {$this->lastName}"));
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;

        return $this->password = null;
    }

    public function encodePassword($password): string
    {
        if (!$password) {
            return $this->getPassword();
        }
        $passwordEncoderFactory = new PasswordHasherFactory([User::class => new NativePasswordHasher()]);
        $encoder = $passwordEncoderFactory->getPasswordHasher($this);
        return $encoder->hash($password);
    }

    public function __toString()
    {
        return trim($this->getName()) ?: ($this->company ?: $this->displayName);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(?string $mobile): static
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getTokenDate(): ?\DateTimeInterface
    {
        return $this->tokenDate;
    }

    public function setTokenDate(?\DateTimeInterface $tokenDate): static
    {
        $this->tokenDate = $tokenDate;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): static
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function isEmailVerified(): ?bool
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $emailVerified): static
    {
        $this->emailVerified = $emailVerified;

        return $this;
    }

    public function isMobileVerified(): ?bool
    {
        return $this->mobileVerified;
    }

    public function setMobileVerified(bool $mobileVerified): static
    {
        $this->mobileVerified = $mobileVerified;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeInterface $dateOfBirth): static
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
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

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

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

    public function getPan(): ?string
    {
        return $this->pan;
    }

    public function setPan(?string $pan): static
    {
        $this->pan = $pan;

        return $this;
    }

    public function getGstNumber(): ?string
    {
        return $this->gstNumber;
    }

    public function setGstNumber(?string $gstNumber): static
    {
        $this->gstNumber = $gstNumber;

        return $this;
    }

    public function getGstTreatment(): ?string
    {
        return $this->gstTreatment;
    }

    public function setGstTreatment(?string $gstTreatment): static
    {
        $this->gstTreatment = $gstTreatment;

        return $this;
    }

    public function getPlaceOfContact(): ?string
    {
        return $this->placeOfContact;
    }

    public function setPlaceOfContact(?string $placeOfContact): static
    {
        $this->placeOfContact = $placeOfContact;

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

    public function getAdditionalEmail(): ?string
    {
        return $this->additionalEmail;
    }

    public function setAdditionalEmail(?string $additionalEmail): static
    {
        $this->additionalEmail = $additionalEmail;

        return $this;
    }

    public function isOfflinePayment(): ?bool
    {
        return $this->offlinePayment;
    }

    public function setOfflinePayment(bool $offlinePayment): static
    {
        $this->offlinePayment = $offlinePayment;

        return $this;
    }

    /**
     * @return Collection<int, Address>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(Address $address): static
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
            $address->setUser($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): static
    {
        if ($this->addresses->removeElement($address)) {
            // set the owning side to null (unless already changed)
            if ($address->getUser() === $this) {
                $address->setUser(null);
            }
        }

        return $this;
    }

    // In src/Entity/User.php

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logicCode = null;

    public function getLogicCode(): ?string
    {
        return $this->logicCode;
    }

    public function setLogicCode(?string $logicCode): static
    {
        $this->logicCode = $logicCode;

        return $this;
    }
    public function getSchool(): ?School
{
    return $this->school;
}

public function setSchool(?School $school): self
{
    $this->school = $school;
    return $this;
}


}
