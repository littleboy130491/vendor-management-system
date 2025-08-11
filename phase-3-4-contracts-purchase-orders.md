# Phase 3 & 4: Contract Management + Purchase Orders & Invoicing
**Duration:** 2.5-3 weeks  
**Team:** 2-3 developers  
**Prerequisites:** Phase 0, Phase 1, and Phase 2 completed successfully

## Overview
These phases implement contract lifecycle management following RFQ awards, and the complete purchase order and invoicing workflow. This includes contract creation, renewal management, purchase order generation, invoice processing, and payment tracking.

## Phase 3: Contract Management

### Objectives
- Implement contract creation from awarded RFQs
- Build contract lifecycle management system
- Create contract renewal and expiration tracking
- Implement contract document management
- Set up automated contract notifications

### Tasks Breakdown

#### 1. Database Schema Implementation
**Estimated Time:** 1 day

```php
// Migration for contracts table
Schema::create('contracts', function (Blueprint $table) {
    $table->id();
    $table->string('contract_number')->unique();
    $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
    $table->foreignId('rfq_id')->nullable()->constrained()->onDelete('set null');
    $table->string('title');
    $table->text('description')->nullable();
    $table->date('start_date');
    $table->date('end_date');
    $table->enum('status', ['draft', 'active', 'expired', 'terminated', 'renewed'])->default('draft');
    $table->text('terms')->nullable();
    $table->decimal('contract_value', 15, 2)->nullable();
    $table->json('deliverables')->nullable();
    $table->json('payment_terms')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    
    $table->index(['status', 'end_date']);
    $table->index(['vendor_id', 'status']);
});

// Migration for contract_renewals table
Schema::create('contract_renewals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('contract_id')->constrained()->onDelete('cascade');
    $table->date('renewal_date');
    $table->date('new_end_date');
    $table->text('notes')->nullable();
    $table->decimal('new_value', 15, 2)->nullable();
    $table->json('updated_terms')->nullable();
    $table->foreignId('renewed_by')->constrained('users');
    $table->timestamps();
});
```

#### 2. Contract Model and Business Logic
**Estimated Time:** 1.5 days

```php
class Contract extends Model
{
    use HasFactory, HasMedia;
    
    protected $fillable = [
        'contract_number', 'vendor_id', 'rfq_id', 'title', 'description',
        'start_date', 'end_date', 'status', 'terms', 'contract_value',
        'deliverables', 'payment_terms', 'created_by'
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'contract_value' => 'decimal:2',
        'deliverables' => 'array',
        'payment_terms' => 'array'
    ];
    
    // Relationships
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function rfq() { return $this->belongsTo(RFQ::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function renewals() { return $this->hasMany(ContractRenewal::class); }
    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class); }
    
    // Methods
    public function isActive(): bool
    {
        return $this->status === 'active' 
            && $this->start_date <= now()->toDateString()
            && $this->end_date >= now()->toDateString();
    }
    
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->end_date <= now()->addDays($days)->toDateString();
    }
    
    public function generateContractNumber(): string
    {
        $year = now()->year;
        $sequence = Contract::whereYear('created_at', $year)->count() + 1;
        return "CON-{$year}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}

class ContractService
{
    public function createFromRFQ(RFQ $rfq, RFQResponse $winningResponse, array $contractData): Contract
    {
        return DB::transaction(function () use ($rfq, $winningResponse, $contractData) {
            $contract = Contract::create([
                'contract_number' => $this->generateContractNumber(),
                'vendor_id' => $winningResponse->vendor_id,
                'rfq_id' => $rfq->id,
                'title' => $contractData['title'] ?? $rfq->title,
                'description' => $contractData['description'] ?? $rfq->description,
                'start_date' => $contractData['start_date'],
                'end_date' => $contractData['end_date'],
                'contract_value' => $winningResponse->quoted_amount,
                'deliverables' => $contractData['deliverables'] ?? null,
                'payment_terms' => $contractData['payment_terms'] ?? null,
                'terms' => $contractData['terms'] ?? null,
                'created_by' => auth()->id(),
                'status' => 'draft'
            ]);
            
            activity('contract_created')
                ->performedOn($contract)
                ->causedBy(auth()->user())
                ->log('Contract created from RFQ: ' . $rfq->title);
                
            return $contract;
        });
    }
    
    public function renewContract(Contract $contract, array $renewalData): ContractRenewal
    {
        return DB::transaction(function () use ($contract, $renewalData) {
            $renewal = ContractRenewal::create([
                'contract_id' => $contract->id,
                'renewal_date' => now()->toDateString(),
                'new_end_date' => $renewalData['new_end_date'],
                'new_value' => $renewalData['new_value'] ?? $contract->contract_value,
                'updated_terms' => $renewalData['updated_terms'] ?? null,
                'notes' => $renewalData['notes'] ?? null,
                'renewed_by' => auth()->id()
            ]);
            
            // Update original contract
            $contract->update([
                'end_date' => $renewalData['new_end_date'],
                'contract_value' => $renewalData['new_value'] ?? $contract->contract_value,
                'status' => 'renewed'
            ]);
            
            // Notify vendor
            $this->notifyVendorOfRenewal($contract, $renewal);
            
            activity('contract_renewed')
                ->performedOn($contract)
                ->causedBy(auth()->user())
                ->log('Contract renewed until: ' . $renewalData['new_end_date']);
                
            return $renewal;
        });
    }
}
```

#### 3. Contract Filament Resource
**Estimated Time:** 1.5 days

Contract resource with comprehensive forms, renewal management, and document handling.

## Phase 4: Purchase Orders & Invoicing

### Objectives
- Implement purchase order creation and management
- Build invoice submission and approval workflow
- Create payment tracking and reconciliation
- Implement vendor payment portal
- Set up automated notifications for payment workflows

### Tasks Breakdown

#### 1. Database Schema Implementation
**Estimated Time:** 1 day

```php
// Migration for purchase_orders table
Schema::create('purchase_orders', function (Blueprint $table) {
    $table->id();
    $table->string('po_number')->unique();
    $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');
    $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
    $table->foreignId('issued_by')->constrained('users');
    $table->decimal('total_amount', 15, 2);
    $table->enum('status', ['draft', 'approved', 'sent', 'acknowledged', 'delivered', 'completed', 'cancelled'])->default('draft');
    $table->date('issued_date');
    $table->date('expected_delivery_date')->nullable();
    $table->date('actual_delivery_date')->nullable();
    $table->text('notes')->nullable();
    $table->json('delivery_address')->nullable();
    $table->timestamps();
    
    $table->index(['status', 'issued_date']);
    $table->index(['vendor_id', 'status']);
});

// Migration for po_items table
Schema::create('po_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
    $table->string('item_name');
    $table->text('description')->nullable();
    $table->integer('quantity');
    $table->decimal('unit_price', 10, 2);
    $table->decimal('line_total', 15, 2);
    $table->string('unit_of_measure')->nullable();
    $table->timestamps();
});

// Migration for invoices table
Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->string('invoice_number');
    $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
    $table->foreignId('purchase_order_id')->nullable()->constrained()->onDelete('set null');
    $table->decimal('amount', 15, 2);
    $table->enum('status', ['submitted', 'under_review', 'approved', 'rejected', 'paid', 'disputed'])->default('submitted');
    $table->date('invoice_date');
    $table->date('due_date');
    $table->timestamp('submitted_at');
    $table->timestamp('approved_at')->nullable();
    $table->foreignId('approved_by')->nullable()->constrained('users');
    $table->text('rejection_reason')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    
    $table->unique(['vendor_id', 'invoice_number']);
    $table->index(['status', 'due_date']);
});

// Migration for payments table
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
    $table->string('payment_reference')->unique();
    $table->decimal('amount', 15, 2);
    $table->enum('method', ['bank_transfer', 'cheque', 'card', 'ach', 'wire'])->default('bank_transfer');
    $table->date('paid_date');
    $table->foreignId('processed_by')->constrained('users');
    $table->text('notes')->nullable();
    $table->json('bank_details')->nullable();
    $table->timestamps();
});
```

#### 2. Purchase Order and Invoice Models
**Estimated Time:** 2 days

```php
class PurchaseOrder extends Model
{
    use HasFactory, HasMedia;
    
    protected $fillable = [
        'po_number', 'contract_id', 'vendor_id', 'issued_by', 'total_amount',
        'status', 'issued_date', 'expected_delivery_date', 'actual_delivery_date',
        'notes', 'delivery_address'
    ];
    
    protected $casts = [
        'total_amount' => 'decimal:2',
        'issued_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'delivery_address' => 'array'
    ];
    
    // Relationships
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function contract() { return $this->belongsTo(Contract::class); }
    public function issuer() { return $this->belongsTo(User::class, 'issued_by'); }
    public function items() { return $this->hasMany(POItem::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    
    // Methods
    public function generatePONumber(): string
    {
        $year = now()->year;
        $sequence = PurchaseOrder::whereYear('created_at', $year)->count() + 1;
        return "PO-{$year}-" . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
    
    public function calculateTotal(): void
    {
        $total = $this->items()->sum(DB::raw('quantity * unit_price'));
        $this->update(['total_amount' => $total]);
    }
}

class Invoice extends Model
{
    use HasFactory, HasMedia;
    
    protected $fillable = [
        'invoice_number', 'vendor_id', 'purchase_order_id', 'amount',
        'status', 'invoice_date', 'due_date', 'submitted_at',
        'approved_at', 'approved_by', 'rejection_reason', 'notes'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime'
    ];
    
    // Relationships
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function payments() { return $this->hasMany(Payment::class); }
    
    // Accessors
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'submitted' => 'warning',
            'under_review' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'paid' => 'primary',
            'disputed' => 'danger',
            default => 'gray'
        };
    }
    
    public function isOverdue(): bool
    {
        return $this->due_date < now()->toDateString() && $this->status !== 'paid';
    }
    
    public function getTotalPaidAttribute(): float
    {
        return $this->payments()->sum('amount');
    }
    
    public function getRemainingBalanceAttribute(): float
    {
        return $this->amount - $this->total_paid;
    }
}

class PurchaseOrderService
{
    public function createPO(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'po_number' => $this->generatePONumber(),
                'contract_id' => $data['contract_id'] ?? null,
                'vendor_id' => $data['vendor_id'],
                'issued_by' => auth()->id(),
                'issued_date' => $data['issued_date'] ?? now()->toDateString(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'status' => 'draft'
            ]);
            
            // Create PO items
            foreach ($data['items'] as $item) {
                $po->items()->create([
                    'item_name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['quantity'] * $item['unit_price'],
                    'unit_of_measure' => $item['unit_of_measure'] ?? null
                ]);
            }
            
            $po->calculateTotal();
            
            activity('po_created')
                ->performedOn($po)
                ->causedBy(auth()->user())
                ->log('Purchase Order created: ' . $po->po_number);
                
            return $po;
        });
    }
    
    public function approvePO(PurchaseOrder $po): void
    {
        DB::transaction(function () use ($po) {
            $po->update(['status' => 'approved']);
            
            // Send PO to vendor
            $this->sendPOToVendor($po);
            
            activity('po_approved')
                ->performedOn($po)
                ->causedBy(auth()->user())
                ->log('Purchase Order approved and sent to vendor');
        });
    }
}

class InvoiceService
{
    public function submitInvoice(Vendor $vendor, array $data): Invoice
    {
        return DB::transaction(function () use ($vendor, $data) {
            $invoice = Invoice::create([
                'invoice_number' => $data['invoice_number'],
                'vendor_id' => $vendor->id,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'amount' => $data['amount'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'submitted_at' => now(),
                'notes' => $data['notes'] ?? null,
                'status' => 'submitted'
            ]);
            
            // Notify finance team
            $this->notifyFinanceTeam($invoice);
            
            activity('invoice_submitted')
                ->performedOn($invoice)
                ->causedBy($vendor->user)
                ->log('Invoice submitted: ' . $invoice->invoice_number);
                
            return $invoice;
        });
    }
    
    public function approveInvoice(Invoice $invoice, User $approver): void
    {
        DB::transaction(function () use ($invoice, $approver) {
            $invoice->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approver->id
            ]);
            
            // Notify vendor of approval
            $this->notifyVendorOfApproval($invoice);
            
            // Schedule payment if auto-payment is enabled
            $this->schedulePaymentIfEnabled($invoice);
            
            activity('invoice_approved')
                ->performedOn($invoice)
                ->causedBy($approver)
                ->log('Invoice approved: ' . $invoice->invoice_number);
        });
    }
    
    public function processPayment(Invoice $invoice, array $paymentData): Payment
    {
        return DB::transaction(function () use ($invoice, $paymentData) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'payment_reference' => $this->generatePaymentReference(),
                'amount' => $paymentData['amount'],
                'method' => $paymentData['method'],
                'paid_date' => $paymentData['paid_date'] ?? now()->toDateString(),
                'processed_by' => auth()->id(),
                'notes' => $paymentData['notes'] ?? null,
                'bank_details' => $paymentData['bank_details'] ?? null
            ]);
            
            // Update invoice status if fully paid
            if ($invoice->remaining_balance <= 0) {
                $invoice->update(['status' => 'paid']);
            }
            
            // Notify vendor of payment
            $this->notifyVendorOfPayment($invoice, $payment);
            
            activity('payment_processed')
                ->performedOn($payment)
                ->causedBy(auth()->user())
                ->log('Payment processed for invoice: ' . $invoice->invoice_number);
                
            return $payment;
        });
    }
}
```

#### 3. Filament Resources for PO & Invoicing
**Estimated Time:** 2 days

Complete Filament resources for purchase orders, invoices, and payments with comprehensive workflows.

#### 4. Notification System Enhancement
**Estimated Time:** 1 day

```php
class POApproved extends Notification
{
    use Queueable;
    
    protected $po;
    
    public function __construct(PurchaseOrder $po)
    {
        $this->po = $po;
    }
    
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Purchase Order Approved: ' . $this->po->po_number)
            ->line('Your purchase order has been approved.')
            ->line('PO Number: ' . $this->po->po_number)
            ->line('Amount: $' . number_format($this->po->total_amount, 2))
            ->line('Expected Delivery: ' . $this->po->expected_delivery_date?->format('M j, Y'))
            ->action('View Purchase Order', url('/vendor/purchase-orders/' . $this->po->id))
            ->line('Please proceed with delivery as per the purchase order terms.');
    }
}

class InvoiceApproved extends Notification
{
    // Similar structure for invoice approval notifications
}

class PaymentProcessed extends Notification
{
    // Similar structure for payment notifications
}
```

#### 5. Testing Implementation
**Estimated Time:** 2 days

Comprehensive test suite covering:
- Purchase order creation and approval workflows
- Invoice submission and approval processes
- Payment processing and reconciliation
- Contract renewal and expiration tracking
- Integration tests for the complete procure-to-pay cycle

## Quality Assurance Checklist

### Functional Tests
- [ ] Contracts can be created from awarded RFQs
- [ ] Contract renewal process works correctly
- [ ] Purchase orders can be created and approved
- [ ] Invoice submission and approval workflow functional
- [ ] Payment processing and tracking operational
- [ ] Automated notifications sent at appropriate stages
- [ ] Document management works for all entity types
- [ ] Reporting and export functions working

### Business Process Tests
- [ ] Complete procure-to-pay cycle functional
- [ ] Three-way matching (PO, Receipt, Invoice) implemented
- [ ] Contract expiration alerts working
- [ ] Overdue invoice tracking functional
- [ ] Vendor payment portal accessible and functional

### Security & Authorization Tests
- [ ] Vendors can only access their own data
- [ ] Proper approval workflows enforced
- [ ] Financial data properly protected
- [ ] Audit trails maintained for all transactions

## Expected Deliverables

1. **Contract Management System**
   - Contract creation from RFQs
   - Renewal and expiration management
   - Document storage and version control

2. **Purchase Order System**
   - PO creation and approval workflow
   - Item management and total calculations
   - Vendor acknowledgment system

3. **Invoice & Payment System**
   - Invoice submission portal for vendors
   - Approval workflow for finance team
   - Payment processing and tracking
   - Reconciliation capabilities

4. **Notification Framework**
   - Email notifications for all workflow stages
   - Dashboard notifications for urgent items
   - Automated reminders for overdue items

5. **Comprehensive Testing Suite**
   - Unit tests for all business logic
   - Feature tests for complete workflows
   - Integration tests for cross-module functionality

## Success Criteria

✅ **Phases 3 & 4 Complete When:**
1. Complete procure-to-pay cycle operational end-to-end
2. Contract lifecycle properly managed with renewals
3. Purchase order approval workflow functional
4. Invoice submission and approval system working
5. Payment processing and tracking operational
6. All notifications sent at appropriate times
7. Vendor portal allows self-service for POs and invoices
8. Financial reporting capabilities functional
9. All tests passing with minimum 80% coverage
10. System ready for reporting and analytics (Phase 5)

## Next Phase Preparation

**Preparation for Phase 5 (Reporting & Analytics):**
- All transaction data properly structured for reporting
- Financial metrics and KPIs identified
- Data aggregation models prepared
- Export functionality ready for enhancement

---

**Dependencies:** Phase 0 (Foundation), Phase 1 (Vendor Management), Phase 2 (RFQ Management)  
**Risks:** Complex financial calculations, multi-step approval workflows, payment integration security  
**Mitigation:** Thorough testing of calculations, staged approval rollout, security audit of financial data
