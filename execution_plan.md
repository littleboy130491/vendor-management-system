# Vendor Management System — Execution Plan & Dev Scaffold

**Audience:** Developers, Tech Leads, DevOps

**Stack:** Laravel 12+, Filament 3.x, MySQL/Postgres, Redis, AWS S3

---

## 1 — Executive Summary

This document is a turnkey execution plan for building the Vendor Management System (VMS) using Laravel + Filament only. It contains:

* Database schema (tables & fields)
* Migration & model scaffolding commands
* Filament Resources mapping (admin & vendor panels)
* Policies, Services, and Event rules
* Seeders and initial data
* Testing, CI, and deployment checklist
* Timeline & acceptance criteria

Use this as the canonical plan to onboard devs and start development.

---

## 2 — High-level Modules

1. Authentication & Roles (Spatie)
2. Vendor Management
3. Procurement / RFQ
4. Contracts
5. Purchase Orders
6. Invoices & Payments
7. Reviews & Warnings
8. Notifications & Scheduler
9. Reporting & Exports

---

## 3 — Database Schema (Detailed)

Below are the core tables and their main columns. Add `id`, `created_at`, `updated_at` unless noted.

### users

* id (PK)
* name
* email (unique)
* password
* status (enum: pending, active, suspended)
* remember\_token

### roles (via spatie)

* id, name, guard\_name

### model\_has\_roles / role\_has\_permissions (spatie)

* default pivot tables

### vendors

* id
* user\_id (FK -> users.id) nullable (support for vendor without a user account initially)
* company\_name
* slug
* category\_id (FK)
* contact\_name
* contact\_email
* contact\_phone
* address
* tax\_id
* status (enum: pending, active, suspended, blacklisted)
* rating\_average (decimal)
* metadata (json)

### vendor\_categories

* id, name, description

### vendor\_documents

* id
* vendor\_id
* collection\_name (string) -- for medialibrary
* file\_name
* file\_size
* mime\_type
* custom\_properties (json)
* expires\_at (nullable)

### rfqs

* id
* title
* slug
* description (text)
* created\_by (FK -> users.id)
* starts\_at, ends\_at
* status (draft, published, closed)
* scope (json)

### rfq\_vendors

* id
* rfq\_id
* vendor\_id
* invited\_at
* status (invited, responded, declined)

### rfq\_responses

* id
* rfq\_id
* vendor\_id
* amount (decimal)
* delivery\_time\_days
* notes
* attachments (json or use medialibrary)
* status (submitted, shortlisted, rejected)

### contracts

* id
* vendor\_id
* rfq\_id (nullable)
* title
* contract\_file (medialibrary) reference
* start\_date
* end\_date
* status (draft, active, expired, terminated)
* terms (text)

### contract\_renewals

* id
* contract\_id
* renewal\_date
* new\_end\_date
* note

### purchase\_orders

* id
* contract\_id (nullable)
* po\_number (unique)
* issued\_by (FK -> users.id)
* vendor\_id
* amount
* status (draft, approved, delivered, paid, cancelled)
* issued\_date

### po\_items

* id
* purchase\_order\_id
* name
* description
* qty (integer)
* unit\_price (decimal)

### invoices

* id
* purchase\_order\_id (nullable)
* vendor\_id
* invoice\_number
* amount
* status (submitted, approved, paid, disputed)
* submitted\_at
* approved\_at
* due\_date

### payments

* id
* invoice\_id
* paid\_at
* amount
* method (bank\_transfer, cheque, card)
* reference

### vendor\_reviews

* id
* vendor\_id
* reviewer\_id
* rating\_quality (1-5)
* rating\_timeliness (1-5)
* rating\_communication (1-5)
* comments

### vendor\_warnings

* id
* vendor\_id
* issued\_by (user\_id)
* type
* details
* resolved\_at

### messages

* id
* sender\_id
* receiver\_id
* subject
* body
* read\_at

---

## 4 — Relationships (Eloquent)

* User hasOne Vendor
* Vendor belongsTo User (optional)
* Vendor belongsTo VendorCategory
* Vendor hasMany VendorDocument
* RFQ belongsTo User (created\_by)
* RFQ belongsToMany Vendor (through rfq\_vendors)
* RFQ hasMany RFQResponse
* RFQResponse belongsTo Vendor
* Contract belongsTo Vendor
* Contract belongsTo RFQ (nullable)
* PurchaseOrder belongsTo Contract
* PurchaseOrder hasMany POItem
* Invoice belongsTo PurchaseOrder
* Invoice belongsTo Vendor
* Payment belongsTo Invoice

---

## 5 — Scaffolding Commands (Artisan)

Run these from project root to scaffold models, migrations, controllers, Filament resources.

### Core models + migrations

```bash
php artisan make:model Vendor -m
php artisan make:model VendorCategory -m
php artisan make:model VendorDocument -m
php artisan make:model RFQ -m
php artisan make:model RFQResponse -m
php artisan make:model Contract -m
php artisan make:model ContractRenewal -m
php artisan make:model PurchaseOrder -m
php artisan make:model POItem -m
php artisan make:model Invoice -m
php artisan make:model Payment -m
php artisan make:model VendorReview -m
php artisan make:model VendorWarning -m
php artisan make:model Message -m
```

### Filament resources (Admin side)

```bash
php artisan make:filament-resource Vendor --generate
php artisan make:filament-resource VendorCategory --generate
php artisan make:filament-resource RFQ --generate
php artisan make:filament-resource RFQResponse --generate
php artisan make:filament-resource Contract --generate
php artisan make:filament-resource PurchaseOrder --generate
php artisan make:filament-resource Invoice --generate
php artisan make:filament-resource VendorReview --generate
php artisan make:filament-resource VendorWarning --generate
php artisan make:filament-resource Message --generate
```

### Policies

```bash
php artisan make:policy VendorPolicy --model=Vendor
php artisan make:policy RFQPolicy --model=RFQ
php artisan make:policy ContractPolicy --model=Contract
php artisan make:policy PurchaseOrderPolicy --model=PurchaseOrder
php artisan make:policy InvoicePolicy --model=Invoice
```

---

## 6 — Filament Panel Setup

**Panels**: create two Filament panels (AdminPanel & VendorPanel) or use one panel with menu visibility rules.

### Example: Create Vendor Panel

* Create `app/Filament/VendorPanel.php` extending `Filament\Panel` (or use Filament's multi-panel instructions)
* Register resources under the Vendor panel namespace
* Apply middleware `auth` and a custom Gate to restrict to `role:vendor` users

### Resource Configuration Tips

* **VendorResource**

  * Forms: use `TextInput`, `Textarea`, `Select` for category, `Repeater` for contacts, `FileUpload` with `spatie/medialibrary` integration
  * Tables: columns for `company_name`, `status`, `rating_average`, actions: Approve, Suspend
* **RFQResource**

  * Form: `BelongsToSelect` for created\_by, `MultiSelect` for invited vendors
  * Table: show `title`, `status`, `starts_at`, `ends_at`
* **InvoiceResource**

  * Actions: Generate PDF (dompdf), Mark as Paid (record Payment)

---

## 7 — Package Integration Details

### spatie/laravel-permission

* Publish and migrate.
* Seed default roles: `super_admin`, `procurement_officer`, `finance_officer`, `vendor`.
* Use `HasRoles` trait on `User` model.

### spatie/laravel-medialibrary

* Add `HasMedia` to Vendor and Contract models.
* Configure S3 disk for `documents` collection.
* Use Filament Media Library plugin for upload UI.

### filament + plugins

* Use `althinect/filament-spatie-roles-permissions` (or Filament official plugin) to manage roles in Admin panel.
* Use `pxlrbt/filament-excel` for export buttons in Filament Resources.

---

## 8 — Key Business Logic & Services

Create `app/Services` for encapsulating domain logic. Examples:

* `VendorOnboardingService` — validation, document checks, send notifications
* `RFQService` — invite vendors, close RFQ, generate comparison
* `ContractService` — start/end, auto-renew, notify
* `PaymentService` — reconcile invoice & payment

Use events and listeners where appropriate (e.g., `VendorApproved`, `InvoiceApproved`).

---

## 9 — Notifications & Scheduler

* Use Laravel Notifications for email & in-app notices.
* For SMS/Slack use the Notification Channels package as needed.
* Schedule tasks:

  * Daily check for contracts expiring in N days → send reminder
  * Send weekly spend summary to finance

Example kernel schedule:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('contracts:check-renewals')->dailyAt('08:00');
}
```

---

## 10 — Seeders (Initial Data)

Seed default roles, permissions, vendor categories, and a super admin user.

```php
// database/seeders/RolePermissionSeeder.php
- create roles
- create permissions (manage vendors, manage rfqs, manage invoices, view reports)
- assign permissions to roles
```

---

## 11 — Testing Checklist

Write tests (Pest recommended) for:

* Registration & vendor approval flow
* RFQ creation & response lifecycle
* Contract expiration reminders
* PO -> Invoice -> Payment flow
* Role-based access for key endpoints and Filament resources

---

## 12 — CI / CD

* GitHub Actions pipeline:

  * Run `composer install --no-progress --prefer-dist`
  * Run migrations in sqlite for tests
  * Execute Pest tests
  * Linting (PHPStan/PHPCS)
* Deploy via Forge/RunCloud:

  * Build steps, queue worker setup, schedule cron, supervisor for queue

---

## 13 — Deployment Checklist

* Environment variables: DB, QUEUE\_CONNECTION, REDIS, S3 keys, MAIL
* Run `php artisan migrate --force`
* Run `php artisan db:seed --class=RolePermissionSeeder --force`
* Configure Supervisor/Horizon for queues
* Configure backups

---

## 14 — Acceptance Criteria (per module)

**Vendor Module**

* Vendor can be created (by admin or via invite)
* Admin approves vendor; vendor status changes and activity log entry created

**RFQ Module**

* Admin can publish RFQ and invite vendors
* Vendor can submit a bid attached to RFQ
* Admin can compare bids

**Contract Module**

* Contract can be uploaded and linked to RFQ/PO
* System sends renewal reminders 30/14/7 days before expiry

**Invoice/Payment**

* Vendor can upload invoice file
* Finance can approve and record payment

---

## 15 — Suggested Development Timeline (Rough)

* Week 0: Project setup, packages, roles seeded
* Week 1–2: Vendor module (CRUD, docs, onboarding)
* Week 3: RFQ module + vendor responses
* Week 4: Contracts + renewals
* Week 5: PO, Invoice, Payment flows
* Week 6: Reports, exports, polishing, tests

---

## 16 — Next Actions (Immediate)

1. Create repo and initial Laravel app.
2. Install Filament and Spatie Permission.
3. Create RolePermissionSeeder and run migrations.
4. Scaffold Vendor models & Filament resource and build UI for onboarding.

---

### Notes

* This plan assumes Filament will be used for both Admin and Vendor panels. If you later want a custom vendor frontend, you can add a lightweight Blade/Inertia layer.
* Keep business rules (SLA calculations, risk scoring) in Services to remain testable and decoupled from controllers.

---

**End of document**
