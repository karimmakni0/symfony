<?php

namespace App\Entity;

    

use App\Repository\ActivitiesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
         
    
#[ORM\Entity(repositoryClass: ActivitiesRepository::class)]
#[ORM\Table(name: "activities")]
class Activities
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private $id;

    #[ORM\Column(length: 255)]
    private $activity_name;

    #[ORM\Column(type: "text", nullable: true)]
    private $activity_description;

    #[ORM\Column(length: 255)]
    private $activity_destination;

    #[ORM\Column(length: 100, nullable: true)]
    private $activity_duration;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2, nullable: true)]
    private $activity_price;

    #[ORM\Column(length: 50, nullable: true)]
    private $activity_genre;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id")]
    private $user;

    #[ORM\Column(type: "date", nullable: true)]
    private $activity_date;

    #[ORM\Column(type: "datetime")]
    private $created_at;

    #[ORM\OneToMany(targetEntity: Resources::class, mappedBy: "activity", cascade: ["persist", "remove"])]
    private $resources;

    // Removing the ORM relationship annotation as we're using activiteId in Billet entity instead
    private $billets;

    #[ORM\Column(type: "integer", nullable: true)]
    private $max_number;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->resources = new ArrayCollection();
        $this->billets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActivityName(): ?string
    {
        return $this->activity_name;
    }

    public function setActivityName(string $activity_name): self
    {
        $this->activity_name = $activity_name;

        return $this;
    }

    public function getActivityDescription(): ?string
    {
        return $this->activity_description;
    }

    public function setActivityDescription(?string $activity_description): self
    {
        $this->activity_description = $activity_description;

        return $this;
    }

    public function getActivityDestination(): ?string
    {
        return $this->activity_destination;
    }

    public function setActivityDestination(string $activity_destination): self
    {
        $this->activity_destination = $activity_destination;

        return $this;
    }

    public function getActivityDuration(): ?string
    {
        return $this->activity_duration;
    }

    public function setActivityDuration(?string $activity_duration): self
    {
        $this->activity_duration = $activity_duration;

        return $this;
    }

    public function getActivityPrice(): ?string
    {
        return $this->activity_price;
    }

    public function setActivityPrice(?string $activity_price): self
    {
        $this->activity_price = $activity_price;

        return $this;
    }

    public function getActivityGenre(): ?string
    {
        return $this->activity_genre;
    }

    public function setActivityGenre(?string $activity_genre): self
    {
        $this->activity_genre = $activity_genre;

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

    public function getActivityDate(): ?\DateTimeInterface
    {
        return $this->activity_date;
    }

    public function setActivityDate(?\DateTimeInterface $activity_date): self
    {
        $this->activity_date = $activity_date;

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

    /**
     * @return Collection|Resources[]
     */
    public function getResources(): Collection
    {
        return $this->resources;
    }

    public function addResource(Resources $resource): self
    {
        if (!$this->resources->contains($resource)) {
            $this->resources[] = $resource;
            $resource->setActivity($this);
        }

        return $this;
    }

    public function removeResource(Resources $resource): self
    {
        if ($this->resources->removeElement($resource)) {
            // set the owning side to null (unless already changed)
            if ($resource->getActivity() === $this) {
                $resource->setActivity(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Billet[]
     */
    public function getBillets(): Collection
    {
        return $this->billets;
    }

    public function addBillet(Billet $billet): self
    {
        if (!$this->billets->contains($billet)) {
            $this->billets[] = $billet;
            $billet->setActiviteId($this->getId());
        }

        return $this;
    }

    public function removeBillet(Billet $billet): self
    {
        if ($this->billets->removeElement($billet)) {
            // Reset the activiteId to null or 0 depending on your database constraints
            $billet->setActiviteId(0);
        }

        return $this;
    }

    public function getMaxNumber(): ?int
    {
        return $this->max_number;
    }

    public function setMaxNumber(?int $max_number): self
    {
        $this->max_number = $max_number;

        return $this;
    }
}
