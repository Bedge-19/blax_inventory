# Blax Inventory

Blax Inventory is a multi-tenant marketplace platform built on **CodeIgniter 4 (PHP 8.1+)**. It supports three roles out of the box:

- **Customers** — browse shops/categories, buy products, request printing services, track orders, manage a cart & checkout via PayMongo.
- **Shop Owners / Tenants** — manage inventory, orders, printing requests, deliveries, POS, withdrawals, and shop settings from a dedicated dashboard.
- **Admins** — verify/approve tenants, manage customers and payouts, resolve compliance issues, review audit logs, and edit site content.

An AI-assisted product search/chat feature is also included, powered by Cohere embeddings.

> This README documents the application layer only. It does not modify or replace any existing configuration, environment, or database files — see `.env.example` for the actual runtime configuration this project expects.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend framework | CodeIgniter 4 |
| Language | PHP ^8.1 |
| Database | MySQL (via MySQLi driver) |
| Frontend | Server-rendered PHP views + Tailwind CSS (CDN) |
| Payments | PayMongo |
| AI / Search | Cohere (embeddings + chat) |
| Testing | PHPUnit 10 |

---

## Project Structure

```
app/
  Controllers/     Auth, Customer, Tenant, Admin, Cart, AiAssistant, NotificationController...
  Models/          Eloquent-style CodeIgniter models (Users, Shops, Products, Orders, ...)
  Views/
    customer/      Customer-facing pages (home, shops, cart, orders, profile...)
    tenant/        Shop-owner dashboard (inventory, orders, POS, deliveries, analytics...)
    admin/         Admin console (tenants, customers, payments, compliance, audit log...)
    components/    Shared layout partials (headers, footers, head/meta)
    auth/          Login, signup, merchant signup, password reset
  Config/          Routes, Filters, App, Database, Cohere, etc.
Admin-prototype/    Static design prototypes for the admin console (not wired into the app)
tenant-prototype/   Static design prototypes for the tenant dashboard (not wired into the app)
final_user_design/  Static design prototypes for customer-facing pages (not wired into the app)
public/             Web root — point your webserver here, never at the project root
tests/              PHPUnit test suite
```

The `*-prototype` and `final_user_design` folders are static HTML/CSS design references used during UI iteration. They are **not** loaded by the running application — the live UI lives entirely under `app/Views`.

---

## Getting Started

### Requirements

- PHP 8.1+
- MySQL
- Composer
- PHP extensions: `intl`, `mbstring`, `json`, `mysqlnd`, `libcurl`

### Setup

```bash
composer install
cp .env.example .env
```

Then edit `.env` with your local values:

- `database.default.*` — your MySQL connection
- `PAYMONGO_PUBLIC_KEY` / `PAYMONGO_SECRET_KEY` — your PayMongo API keys
- `COHERE_API_KEY` — your Cohere API key (for AI-assisted search/chat)
- `email.*` — SMTP settings, used for tenant verification emails

Run migrations and start the built-in server:

```bash
php spark migrate
php spark serve
```

The app will be available at the `app.baseURL` configured in `.env`.

### Running Tests

```bash
composer test
# or
vendor/bin/phpunit
```

---

## Roles & Access

| Role | Entry point | Notes |
|---|---|---|
| Customer | `/` | Default role on signup |
| Shop Owner | `/tenant/*` | Requires admin approval after signup at `/merchant-signup` before the account becomes active |
| Admin | `/admin/*` | Provisioned directly in the database; there is no public admin signup |

Route groups for `tenant/*` and `admin/*` are protected by per-controller session checks (`getShopOrRedirect()` / `checkAdminAuth()`).

---

## Security Notes

This project is under active hardening. Known open items are tracked separately (see `ANTIGRAVITY_PROMPT.md` in this repo for the current remediation plan) and include:

- Removing a development-only password bypass in `Auth::login()`.
- Adding token-based verification to the password reset flow.
- Verifying PayMongo webhook signatures before trusting payment callbacks.
- Re-enabling CSRF protection (`app/Config/Filters.php`) across all state-changing routes.
- Removing committed sample uploads (`public/uploads/`) from version control history.

**Do not deploy this project to a public/production environment until the items above are resolved.**

---

## Contributing

1. Create a branch from `main`.
2. Keep changes scoped — this app serves three very different user roles (customer, tenant, admin) sharing the same codebase, so avoid changes in one area (e.g. `Controllers/Admin.php`) that could affect another (e.g. `Controllers/Tenant.php`) unless the change is explicitly shared logic.
3. Run `composer test` before opening a pull request.
4. Do not commit real uploaded files (permits, logos, product photos) — `public/uploads/` should stay out of version control going forward.

---

## License

MIT — see `LICENSE`.
