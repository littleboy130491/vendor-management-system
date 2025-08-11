# VMS Execution Checklist

## Phase 0: Foundation Setup ⏱️ 3 days

### Day 1: Environment & Packages
```bash
# 1. Verify Laravel Sail is running
./vendor/bin/sail up -d
./vendor/bin/sail ps  # Should show all containers running

# 2. Install core packages
./vendor/bin/sail composer require filament/filament:"^3.0"
./vendor/bin/sail composer require spatie/laravel-permission
./vendor/bin/sail composer require spatie/laravel-medialibrary
./vendor/bin/sail composer require spatie/laravel-activitylog

# 3. Publish configurations
./vendor/bin/sail artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
./vendor/bin/sail artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider"

# 4. Run migrations
./vendor/bin/sail artisan migrate
```

### Day 2: Roles & Permissions
```bash
# 1. Create seeder
./vendor/bin/sail artisan make:seeder RolePermissionSeeder

# 2. Add to seeder file (database/seeders/RolePermissionSeeder.php):
# - Create 4 roles: super_admin, procurement_officer, finance_officer, vendor
# - Create permissions for each module
# - Assign permissions to roles

# 3. Run seeder
./vendor/bin/sail artisan db:seed --class=RolePermissionSeeder

# 4. Create super admin user
./vendor/bin/sail artisan make:command CreateSuperAdmin
./vendor/bin/sail artisan create:super-admin
```

### Day 3: Filament Setup
```bash
# 1. Install Filament panel
./vendor/bin/sail artisan filament:install --panels

# 2. Create admin user
./vendor/bin/sail artisan make:filament-user

# 3. Test login at http://localhost/admin
```

**✅ Phase 0 Complete When:**
- [ ] Can login to Filament admin
- [ ] Roles exist in database
- [ ] Permissions assigned correctly

---

## Phase 1: Vendor Module ⏱️ 1.5 weeks

### Week 1: Core Models & Database
```bash
# Day 1-2: Create models and migrations
./vendor/bin/sail artisan make:model VendorCategory -m
./vendor/bin/sail artisan make:model Vendor -m
./vendor/bin/sail artisan make:model VendorDocument -m
./vendor/bin/sail artisan make:model VendorReview -m
./vendor/bin/sail artisan make:model VendorWarning -m

# Day 3: Create Filament resources
./vendor/bin/sail artisan make:filament-resource Vendor --generate
./vendor/bin/sail artisan make:filament-resource VendorCategory --simple

# Day 4: Create services
mkdir app/Services
# Create VendorOnboardingService.php
# Create VendorRatingService.php

# Day 5: Create seeders
./vendor/bin/sail artisan make:seeder VendorCategorySeeder
./vendor/bin/sail artisan make:factory VendorFactory
```

### Week 2: Testing & Polish
```bash
# Day 1-2: Write tests
./vendor/bin/sail artisan make:test VendorManagementTest
./vendor/bin/sail artisan make:test VendorApprovalTest

# Day 3: Run tests
./vendor/bin/sail test --filter=Vendor
```

**✅ Phase 1 Complete When:**
- [ ] Vendor CRUD working in Filament
- [ ] Approval workflow functional
- [ ] Document upload working
- [ ] Tests passing

---

## Phase 2: RFQ Module ⏱️ 1.5 weeks

### Week 1: Core RFQ System
```bash
# Day 1: Models
./vendor/bin/sail artisan make:model RFQ -m
./vendor/bin/sail artisan make:model RFQResponse -m
./vendor/bin/sail artisan make:model RFQEvaluation -m

# Day 2: Pivot table
./vendor/bin/sail artisan make:migration create_rfq_vendors_table

# Day 3: Filament resources
./vendor/bin/sail artisan make:filament-resource RFQ --generate
./vendor/bin/sail artisan make:filament-resource RFQResponse --generate

# Day 4: Services
# Create RFQService.php
# Create RFQEvaluationService.php

# Day 5: Notifications
./vendor/bin/sail artisan make:notification RFQInvitation
./vendor/bin/sail artisan make:notification RFQAwarded
```

### Week 2: Testing & Integration
```bash
# Test RFQ workflows
./vendor/bin/sail test --filter=RFQ
```

**✅ Phase 2 Complete When:**
- [ ] RFQ creation and publishing works
- [ ] Vendors can submit responses
- [ ] Evaluation scoring functional
- [ ] Award process works

---

## Phase 3: Contracts & POs ⏱️ 2 weeks

### Week 1: Contracts
```bash
# Models and resources
./vendor/bin/sail artisan make:model Contract -m
./vendor/bin/sail artisan make:model ContractRenewal -m
./vendor/bin/sail artisan make:filament-resource Contract --generate

# Service
# Create ContractService.php
```

### Week 2: Purchase Orders
```bash
# Models
./vendor/bin/sail artisan make:model PurchaseOrder -m
./vendor/bin/sail artisan make:model POItem -m
./vendor/bin/sail artisan make:filament-resource PurchaseOrder --generate

# Service
# Create PurchaseOrderService.php
```

**✅ Phase 3 Complete When:**
- [ ] Contracts created from RFQ awards
- [ ] PO creation and approval works
- [ ] Contract renewals tracked

---

## Phase 4: Invoicing & Payments ⏱️ 1.5 weeks

### Week 1: Core System
```bash
# Models
./vendor/bin/sail artisan make:model Invoice -m
./vendor/bin/sail artisan make:model Payment -m
./vendor/bin/sail artisan make:filament-resource Invoice --generate
./vendor/bin/sail artisan make:filament-resource Payment --generate

# Services
# Create InvoiceService.php
# Create PaymentService.php
```

### Week 2: Integration
```bash
# Test complete procure-to-pay cycle
./vendor/bin/sail test --filter=Invoice
./vendor/bin/sail test --filter=Payment
```

**✅ Phase 4 Complete When:**
- [ ] Invoice submission works
- [ ] Approval workflow functional
- [ ] Payments tracked
- [ ] Complete cycle tested

---

## Phase 5: Reporting & Deployment ⏱️ 1.5 weeks

### Week 1: Reporting
```bash
# Create report models
./vendor/bin/sail artisan make:model VendorPerformanceReport -m
./vendor/bin/sail artisan make:model ProcurementReport -m

# Create service
# Create ReportingService.php

# Create command for scheduled reports
./vendor/bin/sail artisan make:command GenerateMonthlyReports

# Create widgets
./vendor/bin/sail artisan make:filament-widget ProcurementStats
./vendor/bin/sail artisan make:filament-widget SpendAnalytics
```

### Week 2: Deployment
```bash
# Optimize for production
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
./vendor/bin/sail artisan view:cache

# Create health check
./vendor/bin/sail artisan make:controller HealthCheckController
```

**✅ Phase 5 Complete When:**
- [ ] Dashboard shows all metrics
- [ ] Reports export correctly
- [ ] Production config ready
- [ ] Health checks pass

---

## Daily Standup Questions

Ask these every morning:
1. What phase/task are we on?
2. Any blockers from yesterday?
3. Do we need to adjust timeline?
4. Are tests passing?

---

## Critical Path Items

**Must work before moving phases:**
- Phase 0 → 1: Authentication working
- Phase 1 → 2: Vendors can be approved
- Phase 2 → 3: RFQs can be awarded
- Phase 3 → 4: POs can be created
- Phase 4 → 5: Payments can be processed

---

## Emergency Fixes

### Common Issues & Solutions

**Migration Errors:**
```bash
# Rollback and retry
./vendor/bin/sail artisan migrate:rollback
./vendor/bin/sail artisan migrate
```

**Filament 403:**
```bash
# Clear cache and check policies
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan permission:cache-reset
```

**Queue Not Processing:**
```bash
# Restart queue worker
./vendor/bin/sail artisan queue:restart
./vendor/bin/sail artisan queue:work
```

**Tests Failing:**
```bash
# Reset test database
./vendor/bin/sail artisan migrate:fresh --env=testing
```

---

## Definition of Done

Each phase is complete when:
1. ✅ All code committed to git
2. ✅ Tests passing (>80% coverage)
3. ✅ Feature demo completed
4. ✅ Documentation updated
5. ✅ Next phase dependencies ready

---

## Quick Wins

If ahead of schedule, add these:
- Phase 1: Bulk vendor import
- Phase 2: RFQ templates
- Phase 3: Contract templates
- Phase 4: Automated payment runs
- Phase 5: Custom report builder

---

*Keep this checklist visible. Check off items as completed.*
