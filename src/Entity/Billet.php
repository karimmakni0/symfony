<?php

namespace App\Entity;

use App\Repository\BilletRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BilletRepository::class)]
#[ORM\Table(name: "billet")]
class Billet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private $id;

    #[ORM\Column(type: "float")]
    private $prix;

    #[ORM\Column(length: 50)]
    private $numero;

    #[ORM\Column(name: "activiteId", type: "integer")]
    private $activiteId;

    #[ORM\Column(type: "integer")]
    private $nb;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getActiviteId(): ?int
    {
        return $this->activiteId;
    }

    public function setActiviteId(int $activiteId): self
    {
        $this->activiteId = $activiteId;

        return $this;
    }

    public function getNb(): ?int
    {
        return $this->nb;
    }

    public function setNb(int $nb): self
    {
        $this->nb = $nb;

        return $this;
    }
}
