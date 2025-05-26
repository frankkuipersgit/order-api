<?php

namespace App\Entity;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['order:read'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?int $orderNumber = null;

    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?\DateTimeImmutable $orderDate = null;

    #[Groups(['order:read'])]
    #[ORM\Column(enumType: OrderStatus::class)]
    private ?OrderStatus $status = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['order:read'])]
    private ?User $user = null;

    #[ORM\Column(length: 10)]
    #[Groups(['order:read'])]
    private ?string $currency = null;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderLine::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['order:read'])]
    private Collection $orderLines;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: Task::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['order:read'])]
    private Collection $tasks;

    public function __construct()
    {
        $this->orderLines = new ArrayCollection();
        $this->tasks = new ArrayCollection();
    }


    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getOrderNumber(): ?int { return $this->orderNumber; }
    public function setOrderNumber(int $orderNumber): static { $this->orderNumber = $orderNumber; return $this; }
    public function getOrderDate(): ?\DateTimeImmutable { return $this->orderDate; }
    public function setOrderDate(\DateTimeImmutable $orderDate): static { $this->orderDate = $orderDate; return $this; }
    public function getStatus(): ?OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus|string $status): static
    {
        if (is_string($status)) {
            $status = OrderStatus::from($status);
        }
        $this->status = $status;
        return $this;
    }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getOrderLines(): Collection { return $this->orderLines; }
    public function addOrderLine(OrderLine $orderLine): static
    {
        if (!$this->orderLines->contains($orderLine)) {
            $this->orderLines->add($orderLine);
            $orderLine->setOrder($this);
        }
        return $this;
    }
    public function removeOrderLine(OrderLine $orderLine): static
    {
        if ($this->orderLines->removeElement($orderLine)) {
            if ($orderLine->getOrder() === $this) {
                $orderLine->setOrder(null);
            }
        }
        return $this;
    }
    public function getTasks(): Collection { return $this->tasks; }
    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setOrder($this);
        }
        return $this;
    }
    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getOrder() === $this) {
                $task->setOrder(null);
            }
        }
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }
}
