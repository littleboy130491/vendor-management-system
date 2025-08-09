# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12+ Vendor Management System (VMS) using Filament 3.x for admin interfaces. The system manages vendor onboarding, RFQ processes, contracts, purchase orders, invoices, and payments. It's designed for procurement teams to streamline vendor relationships and purchase workflows.

**Key Stack:**
- Laravel 12+ with PHP 8.2+
- Filament 3.x (admin panels)
- Tailwind CSS 4.0 with Vite 7
- MySQL/SQLite (SQLite for testing)
- Redis for caching/queues
- Spatie packages (permissions, media library)
- Docker via Laravel Sail

## Development Commands

### Starting Development Environment
```bash
# Start all services (recommended for development)
composer run dev
# This runs: server + queue worker + logs + vite concurrently

# Alternative: individual services
php artisan serve                    # Start Laravel server
php artisan queue:listen --tries=1  # Start queue worker  
php artisan pail --timeout=0        # Watch logs
npm run dev                          # Start Vite dev server

# Docker development
./vendor/bin/sail up -d              # Start all Docker services
./vendor/bin/sail artisan serve     # Laravel server in Docker
```

### Testing
```bash
# Run all tests
composer run test
# This runs: config:clear + artisan test

# Run specific test types
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
vendor/bin/phpunit                   # Direct PHPUnit

# Test with coverage (if configured)
php artisan test --coverage
```

### Code Quality
```bash
# Laravel Pint (code formatting)
vendor/bin/pint

# If PHPStan is added
vendor/bin/phpstan analyse
```

### Database & Migrations
```bash
php artisan migrate                  # Run migrations
php artisan migrate:fresh --seed    # Fresh database with seeders
php artisan db:seed                 # Run seeders only
```

### Asset Building
```bash
npm run build                        # Production build
npm run dev                          # Development with watching
```

## Architecture Overview

### Domain Structure
The application is organized around these main business domains:

1. **Vendor Management** - Core vendor CRUD, approval workflows, ratings
2. **Procurement/RFQ** - Request for Quotes, vendor invitations, bid responses  
3. **Contracts** - Contract management, renewals, document storage
4. **Purchase Orders** - PO creation, line items, approval workflows
5. **Invoices & Payments** - Invoice processing, payment recording, reconciliation
6. **Reviews & Warnings** - Vendor performance tracking, issue management

### Database Design
- Users table with Spatie roles/permissions
- Vendors linked to users (optional), categorized, with documents via Media Library
- RFQs with many-to-many vendor relationships and responses
- Contracts linked to vendors/RFQs with renewal tracking
- Purchase Orders with line items, linked to contracts
- Invoices linked to POs/vendors, with payment records
- Review/warning systems for vendor management

### Service Layer Pattern  
Business logic is encapsulated in service classes:
- `VendorOnboardingService` - vendor creation, approval workflows
- `VendorRatingService` - rating calculations, rankings
- `RFQService` - RFQ management, vendor invitations
- `ContractService` - contract lifecycle, renewals
- `PaymentService` - payment processing, invoice reconciliation

### Filament Integration
- Admin panel for internal users (procurement, finance, super admin)
- Potential vendor panel for vendor users
- Resources auto-generated with `--generate` flag then customized
- Policies control access at resource and record level

### Key Laravel Features Used
- **Eloquent relationships** - Complex domain modeling
- **Events/Listeners** - Vendor approval notifications, status changes  
- **Queues** - Background processing for notifications, reports
- **Policies** - Authorization for different user roles
- **Notifications** - Email/in-app notifications for workflows
- **Spatie Media Library** - Document/file management
- **Activity Log** - Audit trails for vendor actions

## Important Patterns

### Creating New Models
Always use the full scaffold approach:
```bash
php artisan make:model ModelName -m  # Model + migration
php artisan make:policy ModelPolicy --model=ModelName
php artisan make:factory ModelFactory --model=ModelName
php artisan make:filament-resource ModelName --generate
```

### Service Integration  
- Business logic goes in `app/Services/`
- Controllers should be thin, delegating to services
- Use database transactions for multi-step operations
- Fire events for important state changes

### Testing Strategy
- Feature tests for complete workflows (vendor approval, RFQ process)  
- Unit tests for service classes and business logic
- Policy tests for authorization rules
- Use factories for test data generation

## Development Workflow

### Adding New Features
1. Create/modify migrations for database changes
2. Update/create Eloquent models with relationships
3. Build service classes for business logic
4. Create/update Filament resources for UI
5. Implement authorization via policies  
6. Add comprehensive tests
7. Run `composer run dev` to test integration

### Code Style
- Follow Laravel conventions and PSR standards
- Use Laravel Pint for consistent formatting
- Prefer explicit over implicit (clear method names, type hints)
- Leverage Laravel's built-in features before custom solutions

### Database Changes
- Always create migrations, never edit existing ones in production
- Add proper indexes for performance
- Use foreign key constraints with appropriate cascade behavior
- Consider data seeding for development/demo environments

## Docker Development

The project includes Laravel Sail for containerized development:
- MySQL 8.0, Redis, Meilisearch for full-text search
- Mailpit for email testing (accessible at localhost:8025)  
- Selenium for browser testing

Use `./vendor/bin/sail` prefix for all Artisan commands in Docker environment.

## Project Documentation Structure

### How to Use These Documents:

1. **Start with `development-roadmap.md`** - Master plan with 5-phase development strategy
   - Understand overall project goals and timeline (8-10 weeks)
   - Review business logic decisions and architectural choices
   - Check risk management and mitigation strategies
   - Use as reference for stakeholder communications

2. **Use `execution-checklist.md`** - Daily development working document  
   - Step-by-step commands for each phase
   - Copy/paste bash commands for rapid development
   - Check off completed tasks to track progress
   - Reference troubleshooting section for common issues

3. **Reference detailed phase files** when implementing:
   - `phase-0-project-setup.md` - Foundation setup details
   - `phase-1-vendor-management.md` - Vendor module implementation
   - `phase-2-rfq-management.md` - RFQ system details
   - `phase-3-4-contracts-purchase-orders.md` - Contract and PO workflows
   - `phase-5-reporting-deployment.md` - Reporting and production deployment

4. **Update checkboxes** in execution-checklist.md as tasks complete
   - Maintain visibility into project progress
   - Use phase completion criteria before advancing
   - Document any deviations from the plan

### Phase Dependencies:
- **Phase 0 → 1:** Authentication and roles working
- **Phase 1 → 2:** Vendors can be approved
- **Phase 2 → 3:** RFQs can be awarded 
- **Phase 3 → 4:** Purchase orders can be created
- **Phase 4 → 5:** Payments can be processed

### Critical Success Factors:
- Each phase must reach 80%+ test coverage
- Core workflows must be demonstrated before phase completion
- Database migrations must run without errors
- Filament resources must be accessible with proper authorization

*Keep documentation updated as implementation progresses.*
