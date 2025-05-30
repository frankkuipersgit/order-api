# Symfony Orders API

A RESTful backend service for managing orders, order lines, and tasks, built with the latest stable Symfony and PHP versions.

## Features

- **Create Orders:** Add a new order with one or more order lines.
- **Update Orders:** Modify existing order details, including its lines.
- **Delete Orders:** Remove orders from the system.
- **Update Order Status:** Change order status (`pending`, `processing`, `completed`).
- **Link Tasks to Orders:** Attach one or more tasks to any order.
- **JWT Authorization:** All endpoints are secured using Bearer Token authentication.

## Tech Stack

- PHP (latest stable)
- Symfony (latest stable)
- Composer (dependency management)
- SQLite (default, easy local setup)
- Doctrine ORM
- JWT Auth (LexikJWTAuthenticationBundle)

---

## Getting Started

### 1. Clone the repository

```bash
git clone <https://github.com/frankkuipersgit/order-api>
cd <myordersapi>
```

### 2\. Install dependencies

```bash
composer install
```

### 3\. Configure environment

Copy the example environment file and update if necessary:

```bash
cp .env.example .env
```

> **Do not commit your `.env` or `.env.local` file. Only `.env.example` should be versioned.**

### 4\. Database setup

Create and run migrations:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5\. Generate JWT keys

```bash
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem -aes256 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```

Update your `.env` if your keys are in a non-default location.

### 6\. Run the server

```bash
symfony serve
# or
php -S localhost:8000 -t public
```

---

## API Documentation (Swagger/OpenAPI)

- **OpenAPI file:** See `public/swagger.yaml`

- **Interactive docs:** Open `swagger.yaml` in Swagger Editor.

---

## Authentication & Usage

### 1\. Register

```http
POST /register
{
  "email": "user@example.com",
  "password": "test123"
}
```

### 2\. Obtain a Bearer Token

```http
POST /api/login_check
{
  "email": "user@example.com",
  "password": "test123"
}
```

Use the returned JWT as a `Bearer` token in all further API requests:

```makefile
Authorization: Bearer <token>
```

### 3\. Use the API

See the OpenAPI/Swagger docs or the [cURL examples](#examples) below for example endpoints.

---

## Examples

### Create an Order

```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{
    "name": "My order",
    "orderNumber": 1001,
    "orderDate": "2025-05-25T20:15:00+00:00",
    "status": "pending",
    "currency": "EUR",
    "orderLines": [
      { "amount": 2, "productName": "Widget A", "pickedDate": null }
    ]
  }'
```

> **Note:** The `currency` field can only be set when creating an order. It cannot be modified after creation.

### Add Tasks to Order

```bash
curl -X POST http://localhost:8000/api/orders/1/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{
    "tasks": [
      {
        "name": "First Task",
        "description": "Something to do",
        "executionDate": "2025-07-01T08:00:00+00:00"
      }
    ]
  }'
```

---

## Design Decisions & Assumptions

- **SQLite** is used for easy local setup; can be switched to MySQL/Postgres via Doctrine config.

- **Entities, Repositories, Services, Controllers** are split according to Symfony best practices for separation of concerns and maintainability.

- **JWT Auth** is used for secure, stateless authentication (all endpoints except `/register` and `/api/login_check` require auth).

- **Swagger/OpenAPI** docs are provided via a static yaml for clarity and frontend dev usability.

- **Validation** is minimal for the assignment; can be expanded using Symfony's Validator component.

### Order Entity

The Order entity contains the following fields:

- id: Unique identifier
- name: Order name
- orderNumber: Order number
- orderDate: Date when the order was created
- status: Current status of the order (pending, processing, completed)
- currency: The currency of the order (read-only after creation)
- total: The total amount of the order, calculated from order lines (read-only)
- orderLines: Collection of order lines
- tasks: Collection of tasks associated with the order

---

## Testing

Run all tests:

```bash
vendor/phpunit/phpunit/phpunit
```

## Contact

For any questions, contact frankkuipers06@outlook.com.
