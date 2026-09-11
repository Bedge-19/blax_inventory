# Antigravity Task Prompt — Blax Inventory Hardening & UI Improvement

Paste the block below into Antigravity as your task. It's written so the agent works incrementally, verifies each change, and never touches areas outside its assigned scope.

---

## PROMPT (copy from here down)

You are working inside the existing **Blax Inventory** repository — a CodeIgniter 4 multi-tenant marketplace app with three surfaces: `Controllers/Customer.php` + `Views/customer/*` (customer storefront), `Controllers/Tenant.php` + `Views/tenant/*` (shop-owner dashboard), and `Controllers/Admin.php` + `Views/admin/*` (admin console). Auth lives in `Controllers/Auth.php`. Payments go through PayMongo in `Controllers/Cart.php`.

**Ground rules — follow these on every task:**
1. Do not modify `app/Config/Database.php`, `.env`, `.env.example` values, or any migration file unless a task explicitly says to.
2. Do not change the public method signatures of controllers unless required to fix the bug — other views/routes call these methods and must keep working.
3. After each change, re-read the surrounding function to confirm you haven't broken the ownership/authorization checks that already exist (e.g. `shop_id` checks in `Tenant.php`, `checkAdminAuth()` in `Admin.php`).
4. Make one focused commit per numbered task below, with a message referencing the task number.
5. Do not touch `Admin-prototype/`, `tenant-prototype/`, or `final_user_design/` — these are static design references, not live code.
6. Run `composer test` after each task and fix any regression before moving to the next task.

---

### PHASE 1 — Critical security fixes (do these first, in order)

**1. Remove the login backdoor in `app/Controllers/Auth.php::login()`**
Currently the password check is:
```php
if ($user && (password_verify($password, $user['password_hash']) || $user['password_hash'] === '$2y$10$placeholderhash' || $password === 'password')) {
```
Replace it with a strict check:
```php
if ($user && password_verify($password, $user['password_hash'])) {
```
Confirm no seed/test data anywhere in `app/Database/Seeds` relies on the placeholder hash bypass; if it does, update the seed to insert a real hashed password instead of relying on the bypass.

**2. Fix `Auth::forgotPassword()` to require a real reset token**
Replace the current "email + new password, no verification" flow with a token-based flow:
- Add a `password_reset_tokens` table (migration: `id`, `user_id`, `token_hash`, `expires_at`, `used_at`).
- `POST /forgot-password` with just an email generates a random token, stores its hash, and emails a reset link containing the raw token (reuse the existing SMTP config already in `.env.example` under `email.*`).
- A new `GET/POST /reset-password/(:any)` route validates the token (unexpired, unused, hash matches), lets the user set a new password, then marks the token used.
- Keep the route name `forgot-password` for the "request a reset" step so `Views/auth/forgot_password.php` doesn't need a full rewrite, just its target URL and copy.

**3. Verify PayMongo webhook signatures in `Cart::paymongoWebhook()`**
PayMongo signs webhook requests with a `Paymongo-Signature` header (format `t=...,te=...,li=...`, HMAC-SHA256 over `t.payload` using your webhook signing secret). Add:
- A new `.env` key `PAYMONGO_WEBHOOK_SECRET` (add it to `.env.example` as a placeholder — do not put a real secret in the repo).
- Verification logic at the top of `paymongoWebhook()` that computes the HMAC and rejects (HTTP 400) any request whose signature doesn't match, using `hash_equals()` for the comparison.
- Do not change the idempotency logic or order-completion logic below the signature check — only gate it behind the new check.

**4. Re-enable CSRF protection**
In `app/Config/Filters.php`, uncomment `'csrf'` under `globals.before`. Then:
- Grep all `<form method="post"` in `app/Views` and add the CSRF hidden field (`<?= csrf_field() ?>`) to every one that's missing it.
- Grep all `fetch()`/`XMLHttpRequest` POST calls in inline `<script>` blocks or `public/js` and add the CSRF token header, reading it from a `<meta name="csrf-token">` tag you add to `Views/components/head.php`.
- The PayMongo webhook route and any other legitimate external-caller endpoint (e.g. `payment/webhook`) must be excluded from the CSRF filter via `$filters` route exceptions in `Filters.php` — do not disable CSRF globally to fix this, exclude it per-route.
- Test every form in customer, tenant, and admin views still submits successfully after this change.

**5. Purge committed uploads from git history**
`public/uploads/business_permits/*`, `public/uploads/profiles/*`, `public/uploads/shop_logos/*`, `public/uploads/cms/*`, and `public/uploads/product_images/*` are tracked in git and contain what look like real business permit documents.
- Add `public/uploads/*` to `.gitignore` (keep a `.gitkeep` so the folder structure survives).
- Remove the currently tracked files with `git rm --cached` (this task should not silently delete the developer's local files, only stop tracking them).
- Flag clearly in the commit message and in a note to the maintainer that git history still contains these files and needs a history rewrite (`git filter-repo`) plus a force-push and repo credential rotation for anything sensitive — do not attempt the history rewrite automatically, this needs the maintainer's explicit go-ahead since it force-rewrites shared history.

---

### PHASE 2 — Structural hardening (after Phase 1 is verified working)

**6. Replace per-method auth checks with route filters**
Create `app/Filters/AdminAuth.php` and `app/Filters/TenantAuth.php` implementing `FilterInterface`, doing what `checkAdminAuth()` / `getShopOrRedirect()` currently do for the "redirect if not logged in / wrong role" part. Register them as aliases in `app/Config/Filters.php` and apply them to the `admin` and `tenant` route groups in `app/Config/Routes.php` via `['filter' => 'adminAuth']` / `['filter' => 'tenantAuth']`. Keep the existing per-method calls in place until every route is confirmed still protected by the filter, then remove the now-redundant per-method calls.

**7. Enable a Content-Security-Policy**
Set `$CSPEnabled = true` in `app/Config/App.php` and configure `app/Config/ContentSecurityPolicy.php` to allow the existing CDN sources already in use (`cdn.tailwindcss.com`, `fonts.googleapis.com`, `fonts.gstatic.com`, and the PayMongo checkout domain). Test every page for console CSP violations and adjust the policy rather than disabling it.

**8. Add rate limiting to `login`, `signup`, `forgot-password`**
Use CodeIgniter's `Throttler` service in a small filter applied to these three routes (e.g. 10 attempts per 5 minutes per IP) so the auth fixes above can't just be brute-forced instead.

---

### PHASE 3 — UI/UX improvements

Apply these as incremental, visually-consistent changes using the existing Tailwind design tokens already defined in `app/Views/components/head.php` (the `primary`/`surface`/`on-surface` color scale) — do not introduce a second design system.

**Customer-facing (`app/Views/customer/*`, `app/Views/auth/*`):**
- Add persistent cart item count and order-status badges in the header (`components/marketplace_header.php`) so customers don't have to open the cart to know something changed.
- Add empty-state illustrations/copy for empty cart, no orders yet, no favorites — currently likely blank or plain text.
- Add inline form validation (client-side) on signup/checkout so errors show next to the field instead of only via flashdata banners after a full page reload.
- Add skeleton loading states for the AI-assisted search results and product grids, since Cohere calls add latency.
- Ensure product image aspect ratios are consistent across `shop_storefront.php`, `category_products.php`, and `product_detail.php` — mismatched crops are a common source of a "cheap" feeling in marketplace UIs.
- Add a visible order-tracking timeline (placed → processing → shipped/ready → completed) on `customer/orders.php` rather than a plain status label.

**Tenant dashboard (`app/Views/tenant/*`):**
- Add a low-stock/out-of-stock alert banner on `dashboard.php` and `inventory.php` (the `low_stock_threshold` field already exists in the product model — surface it in the UI).
- Add bulk actions (bulk archive, bulk stock adjust) to `inventory.php` — tenants managing many SKUs will want this over one-by-one edits.
- Add a clearer visual distinction between order statuses in `orders.php` using the color tokens already defined (e.g. amber for pending, blue for processing, green for completed) instead of plain text labels.
- On `pos.php`, add a persistent running-total/receipt panel so cashiers can see the current sale at a glance while searching/scanning.
- Add export-progress feedback on `orders/export` and `deliveries/export` (these currently look like plain downloads with no loading state for large exports).
- Surface withdrawal request status/history more prominently on `withdrawals.php` — tenants care a lot about payout state.

**Shared:**
- Standardize spacing/typography scale between customer, tenant, and admin views — since the Tailwind config already defines a full design-token scale, audit for views that use ad hoc pixel values instead of the tokens.
- Add a lightweight toast/notification component for success/error feedback instead of full-page flashdata banners, for actions that don't need a page reload (e.g. saving settings, adding to cart).

---

### Verification checklist (run before declaring any phase done)

- [ ] `composer test` passes
- [ ] Manual login works with a real password and fails with a wrong one, in all three roles
- [ ] Password reset requires a valid emailed token
- [ ] A forged webhook POST (no/bad signature) is rejected with 400
- [ ] Every existing form still submits successfully with CSRF enabled
- [ ] `git status` shows `public/uploads/*` no longer tracked going forward
- [ ] No console CSP violations on customer, tenant, and admin pages
