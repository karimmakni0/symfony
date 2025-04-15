<?php

namespace App\Entity;

use App\Repository\UpgradeRequestsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UpgradeRequestsRepository::class)]
#[ORM\Table(name: "upgrade_requests")]
class UpgradeRequests
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private $id;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    private $user;

    #[ORM\Column(type: "datetime")]
    private $request_date;

    #[ORM\Column(type: "string", length: 20, nullable: true, options: ["default" => "pending"])]
    private $status = 'pending';

    #[ORM\Column(type: "datetime", nullable: true)]
    private $processed_date;

    #[ORM\Column(type: "text", nullable: true)]
    private $message;

    public function __construct()
    {
        $this->request_date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getRequestDate(): ?\DateTimeInterface
    {
        return $this->request_date;
    }

    public function setRequestDate(\DateTimeInterface $request_date): self
    {
        $this->request_date = $request_date;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getProcessedDate(): ?\DateTimeInterface
    {
        return $this->processed_date;
    }

    public function setProcessedDate(?\DateTimeInterface $processed_date): self
    {
        $this->processed_date = $processed_date;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
