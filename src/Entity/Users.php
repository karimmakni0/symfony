<?php

namespace App\Entity;

use App\Repository\UsersRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

#[ORM\Entity(repositoryClass: UsersRepository::class)]
#[ORM\Table(name: "users")]
class Users implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private $id;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Name is required")]
    private $name;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Last name is required")]
    private $lastname;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: "Email is required")]
    #[Assert\Email(message: "The email '{{ value }}' is not a valid email")]
    private $email;

    #[ORM\Column(length: 255)]
    private $password;

    #[ORM\Column(length: 20, nullable: true)]
    private $gender;

    #[ORM\Column(length: 50)]
    private $role;

    #[ORM\Column(length: 20, nullable: true)]
    private $phone;

    #[ORM\Column(type: "date", nullable: true)]
    private $birthday;

    #[ORM\Column(length: 255, nullable: true)]
    private $verification_code;

    #[ORM\Column(type: "boolean", options: ["default" => true])]
    private $enabled = true;

    #[ORM\Column(type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    private $created_at;

    #[ORM\Column(length: 255, nullable: true)]
    private $image;

    #[ORM\Column(type: "boolean", options: ["default" => false])]
    private $isBanned = false;
    
    #[ORM\Column(length: 255, nullable: true)]
    private $totpSecret;
    
    #[ORM\Column(type: "boolean", options: ["default" => false])]
    private $totpEnabled = false;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verification_code;
    }

    public function setVerificationCode(?string $verification_code): self
    {
        $this->verification_code = $verification_code;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getIsBanned(): ?bool
    {
        return $this->isBanned;
    }

    public function setIsBanned(bool $isBanned): self
    {
        $this->isBanned = $isBanned;

        return $this;
    }

    /**
     * Required methods for UserInterface
     */
    public function getRoles(): array
    {
        // Convert the string role to Symfony's expected format
        $role = match ($this->role) {
            'Admin' => 'ROLE_ADMIN',
            'admin' => 'ROLE_ADMIN',
            'Publicitaire' => 'ROLE_PUBLICATOR',
            default => 'ROLE_USER'
        };

        return [$role];
    }

    public function getSalt(): ?string
    {
        // Not needed when using bcrypt or argon2i as the algorithm in security.yaml
        return null;
    }

    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getUsername(): string
    {
        return (string) $this->email;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
    
    /**
     * Get TOTP authentication secret
     */
    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    /**
     * Set TOTP authentication secret
     */
    public function setTotpSecret(?string $totpSecret): self
    {
        $this->totpSecret = $totpSecret;
        return $this;
    }
    
    /**
     * Check if TOTP authentication is enabled
     */
    public function isTotpEnabled(): bool
    {
        return $this->totpEnabled;
    }

    /**
     * Enable/disable TOTP authentication
     */
    public function setTotpEnabled(bool $totpEnabled): self
    {
        $this->totpEnabled = $totpEnabled;
        return $this;
    }
    
    /**
     * Required for the TwoFactorInterface
     */
    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->totpEnabled && null !== $this->totpSecret;
    }

    /**
     * Required for the TwoFactorInterface
     */
    public function getTotpAuthenticationUsername(): string
    {
        return $this->email;
    }

    /**
     * Required for the TwoFactorInterface
     */
    public function getTotpAuthenticationConfiguration(): TotpConfigurationInterface
    {
        // You can customize these settings
        return new class($this->totpSecret) implements TotpConfigurationInterface {
            private $secret;

            public function __construct(string $secret)
            {
                $this->secret = $secret;
            }

            public function getSecret(): string
            {
                return $this->secret;
            }

            public function getWindow(): int
            {
                return 1; // Allow 30 seconds before and after
            }

            public function getCodeLength(): int
            {
                return 6;
            }

            public function getAlgorithm(): int
            {
                return TotpConfigurationInterface::ALGORITHM_SHA1;
            }

            public function getPeriod(): int
            {
                return 30; // 30-second validity period
            }
        };
    }
}
