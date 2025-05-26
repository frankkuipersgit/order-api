<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrderControllerTest extends WebTestCase
{
    private function registerUser($client, $email = 'testuser@example.com', $password = 'test123')
    {
        $client->request(
            'POST',
            '/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => $password,
            ])
        );
    }

    private function getToken($client, $email = 'testuser@example.com', $password = 'test123')
    {
        $client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => $password,
            ])
        );
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        return $data['token'] ?? null;
    }

    public function testOrderCrudFlow()
    {
        $client = static::createClient();

        // 1. Register & Authenticate
        $this->registerUser($client);
        $token = $this->getToken($client);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token,
        ];

        // 2. Create an Order
        $orderData = [
            "name" => "Test Order",
            "orderNumber" => 1001,
            "orderDate" => "2025-06-01T10:00:00+00:00",
            "status" => "pending",
            "orderLines" => [
                [
                    "amount" => 2,
                    "productName" => "Widget A",
                    "pickedDate" => null
                ]
            ]
        ];
        $client->request('POST', '/api/orders', [], [], $headers, json_encode($orderData));
        $this->assertResponseStatusCodeSame(201);

        $createdOrder = json_decode($client->getResponse()->getContent(), true);
        $orderId = $createdOrder['id'] ?? null;
        $this->assertNotNull($orderId);

        // 3. List Orders
        $client->request('GET', '/api/orders', [], [], $headers);
        $this->assertResponseIsSuccessful();
        $orders = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEmpty($orders);

        // 4. Get the created Order
        $client->request('GET', '/api/orders/' . $orderId, [], [], $headers);
        $this->assertResponseIsSuccessful();
        $order = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals("Test Order", $order['name']);

        // 5. Update the Order
        $updateData = [
            "name" => "Updated Order",
            "orderNumber" => 1002,
            "orderDate" => "2025-06-02T11:00:00+00:00",
            "status" => "processing",
            "orderLines" => [
                [
                    "amount" => 1,
                    "productName" => "Widget B",
                    "pickedDate" => null
                ]
            ]
        ];
        $client->request('PUT', '/api/orders/' . $orderId, [], [], $headers, json_encode($updateData));
        $this->assertResponseIsSuccessful();
        $updatedOrder = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals("Updated Order", $updatedOrder['name']);

        // 6. Update Order Status
        $client->request(
            'PATCH',
            '/api/orders/' . $orderId . '/status',
            [],
            [],
            $headers,
            json_encode(['status' => 'completed'])
        );
        $this->assertResponseIsSuccessful();
        $orderWithUpdatedStatus = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('completed', $orderWithUpdatedStatus['status']);

        // 7. Link Tasks
        $taskData = [
            'tasks' => [
                [
                    'name' => 'First Task',
                    'description' => 'Do something',
                    'executionDate' => '2025-06-03T09:00:00+00:00'
                ]
            ]
        ];
        $client->request(
            'POST',
            '/api/orders/' . $orderId . '/tasks',
            [],
            [],
            $headers,
            json_encode($taskData)
        );
        $this->assertResponseIsSuccessful();
        $orderWithTasks = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEmpty($orderWithTasks['tasks']);
        $this->assertEquals('First Task', $orderWithTasks['tasks'][0]['name']);

        // 8. Update the first Task
        $taskId = $orderWithTasks['tasks'][0]['id'];
        $updatedTaskData = [
            'name' => 'Updated Task',
            'description' => 'Updated description',
            'executionDate' => '2025-06-04T10:00:00+00:00'
        ];
        $client->request(
            'PUT',
            "/api/orders/{$orderId}/tasks/{$taskId}",
            [],
            [],
            $headers,
            json_encode($updatedTaskData)
        );
        $this->assertResponseIsSuccessful();
        $orderWithUpdatedTask = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Updated Task', $orderWithUpdatedTask['tasks'][0]['name']);
        $this->assertEquals('Updated description', $orderWithUpdatedTask['tasks'][0]['description']);

        // 9. Delete the first Task
        $client->request(
            'DELETE',
            "/api/orders/{$orderId}/tasks/{$taskId}",
            [],
            [],
            $headers
        );
        $this->assertResponseIsSuccessful();
        $deletedTaskResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Task deleted', $deletedTaskResponse['status']);

        // 10. Confirm task is removed
        $client->request('GET', '/api/orders/' . $orderId, [], [], $headers);
        $this->assertResponseIsSuccessful();
        $orderAfterTaskDelete = json_decode($client->getResponse()->getContent(), true);
        $this->assertEmpty($orderAfterTaskDelete['tasks']);

        // 11. Delete the Order
        $client->request('DELETE', '/api/orders/' . $orderId, [], [], $headers);
        $this->assertResponseIsSuccessful();
    }

}
