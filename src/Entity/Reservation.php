<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: "reservation")]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private $id;

    #[ORM\Column(name: "dateAchat", length: 255)]
    private $dateAchat;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: "userId", referencedColumnName: "id", nullable: false)]
    private $user;

    #[ORM\ManyToOne(targetEntity: Billet::class)]
    #[ORM\JoinColumn(name: "billetId", referencedColumnName: "id", nullable: false)]
    private $billet;

    #[ORM\Column(name: "nombre", type: "integer")]
    private $nombre;

    #[ORM\Column(name: "prixTotal", type: "float")]
    private $prixTotal;

    #[ORM\Column(name: "prixUnite", type: "float")]
    private $prixUnite;

    #[ORM\Column(name: "statuts", length: 120)]
    private $statuts;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateAchat(): ?string
    {
        return $this->dateAchat;
    }

    public function setDateAchat(string $dateAchat): self
    {
        $this->dateAchat = $dateAchat;

        return $this;
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

    public function getBillet(): ?Billet
    {
        return $this->billet;
    }

    public function setBillet(?Billet $billet): self
    {
        $this->billet = $billet;

        return $this;
    }

    public function getNombre(): ?int
    {
        return $this->nombre;
    }

    public function setNombre(int $nombre): self
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getPrixTotal(): ?float
    {
        return $this->prixTotal;
    }

    public function setPrixTotal(float $prixTotal): self
    {
        $this->prixTotal = $prixTotal;

        return $this;
    }

    public function getPrixUnite(): ?float
    {
        return $this->prixUnite;
    }

    public function setPrixUnite(float $prixUnite): self
    {
        $this->prixUnite = $prixUnite;

        return $this;
    }

    public function getStatuts(): ?string
    {
        return $this->statuts;
    }

    public function setStatuts(string $statuts): self
    {
        $this->statuts = $statuts;

        return $this;
    }
}
