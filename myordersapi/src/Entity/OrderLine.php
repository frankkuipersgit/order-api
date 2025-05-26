<?php

namespace App\Entity;

use App\Repository\OrderLineRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: OrderLineRepository::class)]
class OrderLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?float $amount = null;

    #[ORM\Column(length: 255)]
    #[Groups(['order:read'])]
    private ?string $productName = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeImmutable $pickedDate = null;

    #[ORM\ManyToOne(inversedBy: 'orderLines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }

    public function getPickedDate(): ?\DateTimeImmutable
    {
        return $this->pickedDate;
    }

    public function setPickedDate(?\DateTimeImmutable $pickedDate): static
    {
        $this->pickedDate = $pickedDate;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }


    public function setOrder(?Order $order): self
    {
        $this->order = $order;
        return $this;
    }
}
