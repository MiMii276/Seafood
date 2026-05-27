<?php
// src/Entity/Catering.php

namespace App\Entity;

use App\Repository\CateringRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Product;

#[ORM\Entity(repositoryClass: CateringRepository::class)]
class Catering
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(type:"string", length:255)]
    private string $name;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $description = null;

    #[ORM\Column(type:"datetime")]
    private \DateTime $eventDate;

    #[ORM\Column(type:"integer")]
    private int $numberOfGuests;

    #[ORM\Column(type:"decimal", precision:10, scale:2)]
    private float $price;

    #[ORM\Column(type:"string", length:50)]
    private string $status = 'Pending';

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $createdBy = null;

    #[ORM\Column(type:"datetime")]
    private \DateTime $createdAt;

    #[ORM\Column(type:"datetime", nullable:true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\ManyToMany(targetEntity: Product::class)]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->createdAt = new \DateTime(); // regular DateTime
    }

    // Getters & Setters
    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getEventDate(): \DateTime { return $this->eventDate; }
    public function setEventDate(\DateTime $eventDate): self { $this->eventDate = $eventDate; return $this; }

    public function getNumberOfGuests(): int { return $this->numberOfGuests; }
    public function setNumberOfGuests(int $numberOfGuests): self { $this->numberOfGuests = $numberOfGuests; return $this; }

    public function getPrice(): float { return $this->price; }
    public function setPrice(float $price): self { $this->price = $price; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): self { $this->createdBy = $createdBy; return $this; }

    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function setCreatedAt(\DateTime $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTime { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTime $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    /**
     * @return Collection|Product[]
     */
    public function getProducts(): Collection { return $this->products; }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) $this->products[] = $product;
        return $this;
    }

    public function removeProduct(Product $product): self
    {
        $this->products->removeElement($product);
        return $this;
    }
}
