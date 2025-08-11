<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\LogActivity;

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
                ->causedBy($vendor->user ?? auth()->user())
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
    
    public function rejectInvoice(Invoice $invoice, User $rejector, string $reason): void
    {
        $invoice->update([
            'status' => 'rejected',
            'approved_by' => $rejector->id,
            'approved_at' => now(),
            'rejection_reason' => $reason
        ]);
        
        // Notify vendor of rejection
        $this->notifyVendorOfRejection($invoice, $reason);
        
        activity('invoice_rejected')
            ->performedOn($invoice)
            ->causedBy($rejector)
            ->log('Invoice rejected: ' . $invoice->invoice_number . '. Reason: ' . $reason);
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
            $totalPaid = $invoice->payments()->sum('amount');
            if ($totalPaid >= $invoice->amount) {
                $invoice->update(['status' => 'paid']);
            }
            
            // Notify vendor of payment
            $this->notifyVendorOfPayment($invoice, $payment);
            
            activity('payment_processed')
                ->performedOn($payment)
                ->causedBy(auth()->user())
                ->log('Payment processed for invoice: ' . $invoice->invoice_number . '. Amount: $' . number_format($payment->amount, 2));
                
            return $payment;
        });
    }
    
    public function markDisputed(Invoice $invoice, string $reason): void
    {
        $invoice->update([
            'status' => 'disputed',
            'rejection_reason' => $reason
        ]);
        
        activity('invoice_disputed')
            ->performedOn($invoice)
            ->causedBy(auth()->user())
            ->log('Invoice disputed: ' . $invoice->invoice_number . '. Reason: ' . $reason);
    }
    
    public function resolveDispute(Invoice $invoice): void
    {
        $invoice->update([
            'status' => 'approved',
            'rejection_reason' => null
        ]);
        
        activity('invoice_dispute_resolved')
            ->performedOn($invoice)
            ->causedBy(auth()->user())
            ->log('Invoice dispute resolved: ' . $invoice->invoice_number);
    }
    
    public function getOverdueInvoices(): \Illuminate\Database\Eloquent\Collection
    {
        return Invoice::whereDate('due_date', '<', now())
            ->whereNotIn('status', ['paid', 'rejected'])
            ->with(['vendor', 'purchaseOrder'])
            ->get();
    }
    
    public function getInvoicesDueSoon(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return Invoice::whereBetween('due_date', [now(), now()->addDays($days)])
            ->where('status', 'approved')
            ->with(['vendor', 'purchaseOrder'])
            ->get();
    }
    
    protected function generatePaymentReference(): string
    {
        $year = now()->year;
        $sequence = Payment::whereYear('created_at', $year)->count() + 1;
        return "PAY-{$year}-" . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }
    
    protected function notifyFinanceTeam(Invoice $invoice): void
    {
        // This would typically send notifications to the finance team
        // Implementation depends on your notification system
    }
    
    protected function notifyVendorOfApproval(Invoice $invoice): void
    {
        // This would typically send an email to the vendor about approval
    }
    
    protected function notifyVendorOfRejection(Invoice $invoice, string $reason): void
    {
        // This would typically send an email to the vendor about rejection
    }
    
    protected function notifyVendorOfPayment(Invoice $invoice, Payment $payment): void
    {
        // This would typically send a payment confirmation to the vendor
    }
    
    protected function schedulePaymentIfEnabled(Invoice $invoice): void
    {
        // This would check if auto-payment is enabled and schedule payment
        // Implementation depends on your payment processing system
    }
}