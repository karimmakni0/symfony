<?php

namespace App\Entity;

use App\Repository\CouponRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
#[ORM\Table(name: 'coupon')]
class Coupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 20)]
    private ?string $code = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?float $discount = null;

    #[ORM\Column(name: 'is_percentage', type: 'boolean', options: ['default' => true])]
    private bool $is_percentage = true;

    #[ORM\Column(name: 'usage_limit', type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $usage_limit = null;

    #[ORM\Column(name: 'usage_count', type: 'integer', options: ['default' => 0])]
    private int $usage_count = 0;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $expires_at = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $is_active = true;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $created_at = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtoupper($code);
        return $this;
    }

    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    public function setDiscount(float $discount): self
    {
        $this->discount = $discount;
        return $this;
    }

    public function isPercentage(): bool
    {
        return $this->is_percentage;
    }

    public function setIsPercentage(bool $is_percentage): self
    {
        $this->is_percentage = $is_percentage;
        return $this;
    }

    public function getUsageLimit(): ?int
    {
        return $this->usage_limit;
    }

    public function setUsageLimit(int $usage_limit): self
    {
        $this->usage_limit = $usage_limit;
        return $this;
    }

    public function getUsageCount(): int
    {
        return $this->usage_count;
    }

    public function setUsageCount(int $usage_count): self
    {
        $this->usage_count = $usage_count;
        return $this;
    }

    public function incrementUsageCount(): self
    {
        $this->usage_count++;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expires_at;
    }

    public function setExpiresAt(\DateTimeImmutable $expires_at): self
    {
        $this->expires_at = $expires_at;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): self
    {
        $this->is_active = $is_active;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeImmutable $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function isValid(): bool
    {
        $now = new \DateTimeImmutable();
        return $this->is_active && 
               $this->expires_at > $now && 
               $this->usage_count < $this->usage_limit;
    }

    public function calculateDiscount(float $price): float
    {
        if (!$this->isValid()) {
            return 0;
        }

        if ($this->is_percentage) {
            return $price * ($this->discount / 100);
        } else {
            return min($price, $this->discount);
        }
    }
}
