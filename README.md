# 🧺 LCE Backend (Laundry Care Express) - Modern API

**Version**: 2.0 (Complete)
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
*   **Management**: Pause, Resume, and Cancel flows.

### 2. Operational Logistics
*   **Service Zones**: strict ZIP code validation with day-of-week routing (e.g., "94065 only Mon/Wed").
*   **Holidays**: Block specific dates globally or per-zone.
*   **Recurring Schedules**: Users can set "Pick up every Monday", and the system generates orders automatically.
*   **Processing Sites**: Multi-facility routing logic.

### 3. Financials
*   **Dynamic Pricing**: Price lists based on user location (Zip Code).
*   **Promo Codes**: Percentage or Fixed discounts with validation logic.
*   **Wallet/Credits**: Store credit system for refunds and referrals.

### 4. Admin API
*   Full CRUD for Users, Subscriptions, Zones, Holidays, and Prices.
*   "Login As User" (Impersonation) capability.
*   Financial Dashboard endpoints (MRR, Churn).

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
# Only use this command to get the full demo dataset
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

## 🧪  Testing 

A complete ** Collection** is included in the root directory:
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
│   └── Billing/            # InvoiceService, PaymentService
├── Models/                 # Eloquent Models (User, Pickup, Zone, etc.)
└── Http/Controllers/Api/   # Thin Controllers (Request/Response only)
```
