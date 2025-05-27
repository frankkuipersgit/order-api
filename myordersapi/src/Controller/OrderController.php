<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/orders')]
class OrderController extends AbstractController
{
    private OrderService $orderService;
    private OrderRepository $orderRepository;

    public function __construct(
        OrderService $orderService,
        OrderRepository $orderRepository
    ) {
        $this->orderService    = $orderService;
        $this->orderRepository = $orderRepository;
    }

    #[Route('', methods: ['POST'])]
    public function createOrder(Request $request): JsonResponse
    {
        $data  = json_decode($request->getContent(), true);
        $order = $this->orderService->createOrder($data);

        return $this->json($this->orderToArray($order), 201);
    }

    #[Route('', methods: ['GET'])]
    public function listOrders(): JsonResponse
    {
        $orders = $this->orderRepository->findBy(['user' => $this->getUser()]);
        $result = array_map(fn($order) => $this->orderToArray($order), $orders);

        return $this->json($result, 200);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getOrder(int $id): JsonResponse
    {
        $order = $this->orderRepository->findOneBy([
            'id'   => $id,
            'user' => $this->getUser(),
        ]);

        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        return $this->json($this->orderToArray($order), 200);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function updateOrder(int $id, Request $request): JsonResponse
    {
        $order = $this->orderRepository->findOneBy([
            'id'   => $id,
            'user' => $this->getUser(),
        ]);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        $data  = json_decode($request->getContent(), true);
        $order = $this->orderService->updateOrder($order, $data);

        return $this->json($this->orderToArray($order), 200);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function deleteOrder(int $id): JsonResponse
    {
        $order = $this->orderRepository->findOneBy([
            'id'   => $id,
            'user' => $this->getUser(),
        ]);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        $this->orderService->deleteOrder($order);
        return $this->json(['status' => 'Order deleted']);
    }

    #[Route('/{id}/status', methods: ['PATCH'])]
    public function updateOrderStatus(int $id, Request $request): JsonResponse
    {
        $order = $this->orderRepository->findOneBy([
            'id'   => $id,
            'user' => $this->getUser(),
        ]);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data['status'])) {
            return $this->json(['error' => 'Status required'], 400);
        }

        $order = $this->orderService->updateOrderStatus($order, $data['status']);
        return $this->json($this->orderToArray($order), 200);
    }

    #[Route('/{id}/tasks', methods: ['POST'])]
    public function linkTasks(int $id, Request $request): JsonResponse
    {
        $order = $this->orderRepository->findOneBy([
            'id'   => $id,
            'user' => $this->getUser(),
        ]);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data['tasks']) || !is_array($data['tasks'])) {
            return $this->json(['error' => 'Tasks array required'], 400);
        }

        $order = $this->orderService->linkTasks($order, $data['tasks']);
        return $this->json($this->orderToArray($order), 200);
    }

    #[Route('/{orderId}/tasks/{taskId}', methods: ['PUT'])]
    public function updateTask(int $orderId, int $taskId, Request $request): JsonResponse
    {
        $order = $this->orderRepository->findOneBy([
            'id' => $orderId,
            'user' => $this->getUser(),
        ]);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        $order = $this->orderService->updateTask($order, $taskId, $data);
        if ($order === null) {
            return $this->json(['error' => 'Task not found for this order'], 404);
        }

        return $this->json($this->orderToArray($order), 200);
    }

    #[Route('/{orderId}/tasks/{taskId}', methods: ['DELETE'])]
    public function deleteTask(int $orderId, int $taskId): JsonResponse
    {
        $order = $this->orderRepository->findOneBy([
            'id' => $orderId,
            'user' => $this->getUser(),
        ]);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        $result = $this->orderService->deleteTask($order, $taskId);
        if (!$result) {
            return $this->json(['error' => 'Task not found for this order'], 404);
        }

        return $this->json(['status' => 'Task deleted'], 200);
    }

    private function orderToArray(\App\Entity\Order $order): array
    {
        return [
            'id'          => $order->getId(),
            'name'        => $order->getName(),
            'orderNumber' => $order->getOrderNumber(),
            'orderDate'   => $order->getOrderDate()?->format('Y-m-d H:i:s'),
            'status' => $order->getStatus()?->value,
            'currency'    => $order->getCurrency(),
            'orderLines'  => array_map(function($ol) {
                return [
                    'id'         => $ol->getId(),
                    'amount'     => $ol->getAmount(),
                    'productName'=> $ol->getProductName(),
                    'pickedDate' => $ol->getPickedDate()?->format('Y-m-d H:i:s'),
                ];
            }, $order->getOrderLines()->toArray()),
            'tasks' => array_map(function($task) {
                return [
                    'id'           => $task->getId(),
                    'name'         => $task->getName(),
                    'description'  => $task->getDescription(),
                    'executionDate'=> $task->getExecutionDate()?->format('Y-m-d H:i:s'),
                ];
            }, $order->getTasks()->toArray()),
        ];
    }
}
