# 🧺 LCE Backend (Laundry Care Express) - Modern API

**Version**: 2.1 (LCE 2.0 Feature Set)
**Status**: 🚀 **Production Ready**
**Tech Stack**: Laravel 12, MySQL, Stripe API, Sanctum Auth

A fully modernized, service-driven backend API for a laundry management platform. This project has replaced the legacy SQL-based logic with a robust Laravel Application layer, handling **Pay-Per-Order (PPO)**, **Subscriptions**, **Credits**, and **Logistics**.

---

## 📚 Documentation Center

## 🖥️ Live Demo

We have included a **React-based Demo Frontend** to test the API immediately without any installation.

1.  Start the backend: `php artisan serve`
3.  Login with: `customer@example.com` / `password`

---

## 🛠️ Features Implemented

### 1. Subscription Engine (Stripe Native)
*   **Plans**: Tiered pricing (Silver, Gold, Family) synced with Stripe Products.
*   **Billing**: Automatic monthly billing via Stripe Invoices.
*   **Overage**: Automatically charges PPO rates if user exceeds bag limit.
*   **Banking & Rollover**: Unused bags automatically roll over to the next month ("Banking").
*   **Refunds**: Automated refund calculation for annual cancellations based on policy.
*   **Management**: Pause, Resume, and Cancel flows.

### 2. Operational Logistics
*   **Service Zones**: strict ZIP code validation with day-of-week routing (e.g., "94065 only Mon/Wed").
*   **Holidays**: Block specific dates globally or per-zone.
*   **Recurring Schedules**: Users can set "Pick up every Monday" (Weekly) or Bi-Weekly schedules.
*   **Processing Sites**: Multi-facility routing logic.

### 3. Financials
*   **Dynamic Pricing**: Prices (PPO per lb, Fees) are configurable via database, not hardcoded.
*   **Secure Billing**: All pricing calculations occur server-side to prevent tampering.
*   **Promo Codes**: Percentage or Fixed discounts with validation logic.
*   **Wallet/Credits**: Store credit system for refunds and referrals.
*   **Welcome Credit**: New users automatically receive a $20 welcome credit.

### 4. Admin API
*   **Impersonation**: Admins can "Login As" any user to troubleshoot issues.
*   **Customer Management**: Search and view detailed customer profiles.
*   **Full CRUD**: Users, Subscriptions, Zones, Holidays, and Prices.
*   "Login As User" (Impersonation) capability.
*   Financial Dashboard endpoints (MRR, Churn).

---

## 🔌 Frontend Integration Guide

This backend is designed as a headless API. You can connect any frontend (React, Vue, Mobile App) using standard HTTP requests.

### Authentication
The API uses **Laravel Sanctum** for authentication. The recommended method for external frontends is **Bearer Tokens**.

1.  **Login**: Send a `POST` request to `/api/v1/auth/login` with `email` and `password`.
2.  **Receive Token**: The response will contain a `token` (e.g., `1|AbCdEf...`).
3.  **Authenticate Requests**: Include this token in the `Authorization` header of all subsequent requests:
    ```
    Authorization: Bearer 1|AbCdEf...
    ```

### CORS Configuration
By default, the API permits requests from `localhost:3000` and `*` (wildcard).
*   **Config File**: `config/cors.php`
*   **Allowed Origins**: Update the `allowed_origins` array in `config/cors.php` to include your production frontend domain (e.g., `https://myapp.com`) if you wish to restrict access.

### API Base URL
*   Local Development: `http://localhost:8000/api/v1`
*   Production: `https://api.yourdomain.com/api/v1`

---

## 🚀 Installation & Setup

### Prerequisites
*   PHP 8.2+
*   Composer
*   MySQL 8.0+

### Setup Commands
```bash
# 1. Install Dependencies
composer install

# 2. Environment Setup
cp .env.example .env
php artisan key:generate

# 3. Database & Seeding (Critical!)
# This runs all migrations, including LCE 2.0 features (Credits, Configs, etc.)
php artisan migrate:fresh --seed

# 4. Start Server
php artisan serve
```

### 🗝️ Default Verification Credentials

| Role | Email | Password |
| :--- | :--- | :--- |
| **Admin** | `admin@example.com` | `password` |
| **Customer** | `customer@example.com` | `password` |

---

## 🧪 API Testing (Postman)

A complete **Postman Collection** is included in the root directory:
It is organized by **Persona**:
1.  **Customer Journey**: Auth -> Zone Check -> Subscribe -> Schedule Pickup.
2.  **Admin Operations**: Manage Users, Zones, Pricing, Sites.

---

## 📁 Project Structure

```
app/
├── Services/               # ALL Business Logic lives here
│   ├── Pricing/            # PricingService, PromoService
│   ├── Pickup/             # PickupService, ZoneService, RecurringPickupService
│   ├── Subscription/       # SubscriptionService (Stripe wrapper)
│   ├── Billing/            # InvoiceService, BillingService
│   └── Configuration/      # ConfigurationService (Pricing Config)
├── Models/                 # Eloquent Models (User, Pickup, Zone, Credit, etc.)
└── Http/Controllers/Api/   # Thin Controllers (Request/Response only)
```
