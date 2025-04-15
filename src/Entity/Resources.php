<?php

namespace App\Entity;

use App\Repository\ResourcesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResourcesRepository::class)]
#[ORM\Table(name: "resources")]
class Resources
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private $id;

    #[ORM\Column(length: 255)]
    private $path;

    #[ORM\ManyToOne(targetEntity: Activities::class, inversedBy: "resources")]
    #[ORM\JoinColumn(name: "activity_id", referencedColumnName: "id", nullable: true, onDelete: "CASCADE")]
    private $activity;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getActivity(): ?Activities
    {
        return $this->activity;
    }

    public function setActivity(?Activities $activity): self
    {
        $this->activity = $activity;

        return $this;
    }
}
