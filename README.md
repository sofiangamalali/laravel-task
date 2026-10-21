# Extendable Order and Payment Management API

A robust, enterprise-grade **Laravel RESTful API** for managing orders and processing payments, built following clean code principles, **Repository Pattern**, **Service Layer**, **Form Requests Validation**, and the **Strategy Pattern** for seamless payment gateway extensibility.

---

## 🏛️ Architectural Design & Patterns

The application follows a strict separation of concerns across multiple layers:

```
Request ➡️ Form Request (Validation)
             ⬇️
         Controller (HTTP Handling & JSON Formatting)
             ⬇️
         Service Layer (Business Logic & Transactions)
             ⬇️
 ┌─────────────────────────┬─────────────────────────┐
 │                         │                         │
Repository Pattern    Strategy Pattern        Eloquent Models
(Data Access)      (Payment Gateways)      (Database Entities)
```

### 1. Form Requests (`app/Http/Requests`)
- Dedicated Request classes for all input validation (`StoreOrderRequest`, `ProcessPaymentRequest`, `RegisterRequest`, etc.).
- Centralized validation rules, custom error messages, and standardized JSON error formatting (HTTP 422).

### 2. Service Layer (`app/Services`)
- Encapsulates all domain and business rules away from controllers.
- `OrderService`: Handles order creation with automatic total amount calculations and verifies deletion constraints.
- `PaymentService`: Validates order confirmation rules and orchestrates payment processing through the gateway strategy context.
- `AuthService`: Manages user registration, authentication, JWT tokens, and profiles.

### 3. Repository Pattern (`app/Repositories`)
- Decouples business logic from Eloquent ORM.
- Contracts (`OrderRepositoryInterface`, `PaymentRepositoryInterface`) allow easy mocking in unit tests or swapping persistence layers.
- Implementations (`OrderRepository`, `PaymentRepository`) handle queries, relationships eager loading, filtering, and database transactions.

### 4. Strategy Pattern (`app/Services/Payment`)
- Manages payment processing across multiple payment providers (Credit Card, PayPal, Stripe).
- Adheres strictly to the **Open/Closed Principle (OCP)**: Adding new payment methods requires zero changes to existing payment controllers or services.

---

## 🔒 Business Rules Implemented

| # | Business Rule | Enforcement Mechanism |
|---|---|---|
| **1** | **Payment Confirmation Rule** | Payments can **ONLY** be processed for orders with a `confirmed` status. If an order is `pending` or `cancelled`, an `InvalidOrderStatusForPaymentException` (HTTP 422) is thrown. |
| **2** | **Order Deletion Rule** | Orders **CANNOT be deleted** if they have associated payments. The database enforces a `restrictOnDelete` foreign key, and `OrderService` explicitly checks `hasPayments()` before deletion, throwing `OrderCannotBeDeletedException` (HTTP 422). |
| **3** | **Automatic Order Calculation** | When an order is created or updated, the `total_amount` is calculated automatically from the items' `price * quantity`. |
| **4** | **JWT Authentication** | All endpoints (except `register` and `login`) require a valid Bearer JWT token (`auth:api`). |

---

## 💳 Payment Gateway Strategy Pattern (Extensibility)

The payment architecture consists of:
1. **`PaymentGatewayInterface`**: Contract requiring `pay(PaymentRequestDTO): PaymentResponseDTO` and `getIdentifier()`.
2. **`PaymentGatewayManager`**: The Strategy Resolver and Factory. Resolves gateways by name and allows dynamic runtime registration.
3. **`config/payment.php`**: Gateway mappings and API credentials.

### Supported Gateways Out of the Box:
- `credit_card`: Supports card processing simulation (fails if card number ends in `0000` or `simulate_failure` is passed).
- `paypal`: Supports PayPal checkout simulation (fails if email contains `fail`).
- `stripe`: Supports modern Stripe PaymentIntent / charge simulation.

### 🚀 How to Add a New Payment Gateway (e.g., Apple Pay or Fawry) in 3 Steps:

#### Step 1: Create the Gateway Class
Implement `PaymentGatewayInterface`:

```php
namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\PaymentRequestDTO;
use App\Services\Payment\DTOs\PaymentResponseDTO;

class FawryGateway implements PaymentGatewayInterface
{
    public function __construct(protected array $config = []) {}

    public function getIdentifier(): string
    {
        return 'fawry';
    }

    public function pay(PaymentRequestDTO $request): PaymentResponseDTO
    {
        // Call Fawry API using $this->config['merchant_code']
        $refNumber = 'FAWRY-' . rand(100000, 999999);

        return PaymentResponseDTO::success(
            transactionId: $refNumber,
            message: 'Fawry reference code generated successfully.'
        );
    }
}
```

#### Step 2: Register in `config/payment.php`
```php
'gateways' => [
    // ... other gateways ...
    'fawry' => [
        'class' => \App\Services\Payment\Gateways\FawryGateway::class,
        'merchant_code' => env('FAWRY_MERCHANT_CODE'),
        'security_key' => env('FAWRY_SECURITY_KEY'),
    ],
],
```

#### Step 3: Add Credentials to `.env`
```env
FAWRY_MERCHANT_CODE=12345678
FAWRY_SECURITY_KEY=secret_fawry_key
```

**That's it!** No controller, service, or existing gateway code is touched.

---

## 🛠️ Setup & Installation Instructions

### Prerequisites:
- PHP >= 8.2 with OpenSSL, PDO, Mbstring, and Tokenizer extensions.
- Composer
- SQLite or MySQL

### 1. Clone & Enter Directory:
```bash
cd d:\projects\laravel-task\laravel-task
```

### 2. Environment Setup:
```bash
cp .env.example .env
```

Ensure SQLite database file exists (or configure MySQL in `.env`):
```bash
touch database/database.sqlite
```

### 3. Generate App Key & JWT Secret:
```bash
php artisan key:generate
php artisan jwt:secret
```

### 4. Run Migrations & Seeders:
```bash
php artisan migrate:fresh --seed
```
*This will create the database schema and seed a demo user (`admin@example.com` / `password123`) along with demo orders.*

### 5. Start Development Server:
```bash
php artisan serve
```
The API is now running at `http://127.0.0.1:8000/api`.

---

## 🧪 Running Automated Tests

A comprehensive test suite is provided covering Unit and Feature tests:

```bash
php artisan test
```

### Tests Included:
- **`PaymentGatewayStrategyTest` (Unit)**: Verifies `CreditCardGateway`, `PayPalGateway`, `StripeGateway`, fallback handling, and runtime gateway extension.
- **`AuthTest` (Feature)**: Tests JWT registration, login, profile retrieval, invalid credentials, and authentication guards.
- **`OrderManagementTest` (Feature)**: Tests automatic price calculation, order filtering by status, update, delete without payments, and **order deletion prevention when payments exist**.
- **`PaymentProcessingTest` (Feature)**: Tests payment execution, **payment rejection for unconfirmed orders**, card decline simulation, PayPal capture, and payment retrieval.

---

## 📬 Postman Collection

A complete Postman collection is ready to import:
- Located at: `postman_collection.json` in the project root.
- Includes pre-configured environment variables (`base_url`, `jwt_token`, `order_id`).
- Automatically extracts and stores the JWT Bearer token upon login.

### Endpoints Overview:

| Group | Method | Endpoint | Description | Auth Required |
|---|---|---|---|---|
| **Auth** | `POST` | `/api/auth/register` | Register a new user | No |
| **Auth** | `POST` | `/api/auth/login` | Authenticate and obtain JWT token | No |
| **Auth** | `GET` | `/api/auth/me` | Get authenticated user profile | Yes |
| **Auth** | `POST` | `/api/auth/refresh` | Refresh JWT token | Yes |
| **Auth** | `POST` | `/api/auth/logout` | Invalidate token | Yes |
| **Orders** | `GET` | `/api/orders` | List orders (filters: `status`, `search`, `per_page`) | Yes |
| **Orders** | `POST` | `/api/orders` | Create order (auto-calculates total from items) | Yes |
| **Orders** | `GET` | `/api/orders/{id}` | View single order with items and payments | Yes |
| **Orders** | `PUT` | `/api/orders/{id}` | Update order details | Yes |
| **Orders** | `DELETE`| `/api/orders/{id}` | Delete order (fails if payments exist) | Yes |
| **Payments**| `GET` | `/api/payments/gateways` | View all supported payment gateways | Yes |
| **Payments**| `POST` | `/api/payments` | Process payment for a confirmed order | Yes |
| **Payments**| `GET` | `/api/payments` | List all payments (paginated) | Yes |
| **Payments**| `GET` | `/api/payments/{id}` | View single payment details | Yes |
| **Payments**| `GET` | `/api/orders/{id}/payments` | View all payments for a specific order | Yes |
