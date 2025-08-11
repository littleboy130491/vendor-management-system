<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractRenewal;
use App\Models\RFQ;
use App\Models\RFQResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\LogActivity;

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
    
    public function activateContract(Contract $contract): void
    {
        $contract->update(['status' => 'active']);
        
        activity('contract_activated')
            ->performedOn($contract)
            ->causedBy(auth()->user())
            ->log('Contract activated: ' . $contract->contract_number);
    }
    
    public function terminateContract(Contract $contract, string $reason = null): void
    {
        $contract->update(['status' => 'terminated']);
        
        activity('contract_terminated')
            ->performedOn($contract)
            ->causedBy(auth()->user())
            ->log('Contract terminated: ' . ($reason ?? 'No reason provided'));
    }
    
    public function getExpiringContracts(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Contract::where('status', 'active')
            ->whereDate('end_date', '<=', now()->addDays($days))
            ->whereDate('end_date', '>=', now())
            ->with(['vendor', 'creator'])
            ->get();
    }
    
    protected function generateContractNumber(): string
    {
        $year = now()->year;
        $sequence = Contract::whereYear('created_at', $year)->count() + 1;
        return "CON-{$year}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
    
    protected function notifyVendorOfRenewal(Contract $contract, ContractRenewal $renewal): void
    {
        // This would typically send an email notification to the vendor
        // Implementation depends on your notification preferences
    }
}