<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class OrderService
{
    private EntityManagerInterface $em;
    private Security $security;

    public function __construct(
        EntityManagerInterface $em,
        Security $security,
    ) {
        $this->em = $em;
        $this->security = $security;
    }

    public function createOrder(array $data): Order
    {
        $user = $this->security->getUser();

        $order = new Order();
        $order->setName($data['name'] ?? '');
        $order->setOrderNumber($data['orderNumber'] ?? 0);
        $order->setOrderDate(new \DateTimeImmutable($data['orderDate']));
        $order->setStatus($data['status'] ?? OrderStatus::PENDING);
        $order->setUser($user);

        if (!empty($data['orderLines'])) {
            foreach ($data['orderLines'] as $ol) {
                $line = new OrderLine();
                $line->setAmount($ol['amount']);
                $line->setProductName($ol['productName']);
                $line->setPickedDate(isset($ol['pickedDate']) ? new \DateTimeImmutable($ol['pickedDate']) : null);
                $line->setOrder($order);
                $this->em->persist($line);
                $order->addOrderLine($line);
            }
        }

        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }

    public function updateOrder(Order $order, array $data): Order
    {
        if (isset($data['name'])) $order->setName($data['name']);
        if (isset($data['orderNumber'])) $order->setOrderNumber($data['orderNumber']);
        if (isset($data['orderDate'])) $order->setOrderDate(new \DateTimeImmutable($data['orderDate']));
        if (isset($data['status'])) $order->setStatus($data['status']);

        if (isset($data['orderLines'])) {
            foreach ($order->getOrderLines() as $oldLine) {
                $this->em->remove($oldLine);
            }
            $order->getOrderLines()->clear();

            foreach ($data['orderLines'] as $ol) {
                $line = new OrderLine();
                $line->setAmount($ol['amount']);
                $line->setProductName($ol['productName']);
                $line->setPickedDate(isset($ol['pickedDate']) ? new \DateTimeImmutable($ol['pickedDate']) : null);
                $line->setOrder($order);
                $this->em->persist($line);
                $order->addOrderLine($line);
            }
        }

        $this->em->flush();
        return $order;
    }

    public function deleteOrder(Order $order): void
    {
        $this->em->remove($order);
        $this->em->flush();
    }

    public function updateOrderStatus(Order $order, string $status): Order
    {
        $order->setStatus($status);
        $this->em->flush();
        return $order;
    }

    public function linkTasks(Order $order, array $tasksData): Order
    {
        foreach ($tasksData as $taskData) {
            $task = new Task();
            $task->setName($taskData['name']);
            $task->setDescription($taskData['description'] ?? null);
            $task->setExecutionDate(new \DateTimeImmutable($taskData['executionDate']));
            $task->setOrder($order);
            $this->em->persist($task);
            $order->addTask($task);
        }
        $this->em->flush();
        return $order;
    }

    public function updateTask(Order $order, int $taskId, array $data): ?Order
    {
        $task = null;
        foreach ($order->getTasks() as $t) {
            if ($t->getId() === $taskId) {
                $task = $t;
                break;
            }
        }
        if (!$task) {
            return null;
        }

        if (isset($data['name'])) {
            $task->setName($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $task->setDescription($data['description']);
        }
        if (array_key_exists('executionDate', $data)) {
            $task->setExecutionDate($data['executionDate'] ? new \DateTimeImmutable($data['executionDate']) : null);
        }

        $this->em->flush();

        return $order;
    }

    public function deleteTask(Order $order, int $taskId): bool
    {
        $task = null;
        foreach ($order->getTasks() as $t) {
            if ($t->getId() === $taskId) {
                $task = $t;
                break;
            }
        }
        if (!$task) {
            return false;
        }
        $order->removeTask($task);
        $this->em->remove($task);
        $this->em->flush();

        return true;
    }
}
