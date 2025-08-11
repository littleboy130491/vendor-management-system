<?php

namespace App\Services;

use App\Models\RFQ;
use App\Models\RFQEvaluation;
use App\Models\RFQResponse;
use App\Models\User;
use App\Models\Vendor;
use DomainException;
use Illuminate\Support\Facades\DB;

class RFQService
{
    public function inviteVendors(RFQ $rfq, array $vendorIds): void
    {
        foreach ($vendorIds as $vendorId) {
            if (!$rfq->vendors()->where('vendor_id', $vendorId)->exists()) {
                $rfq->vendors()->attach($vendorId, [
                    'status' => 'invited',
                    'invited_at' => now(),
                ]);
            }
        }
    }

    public function publishRFQ(RFQ $rfq): void
    {
        DB::transaction(function () use ($rfq) {
            $rfq->update([
                'status' => 'published',
                'published_at' => now(),
            ]);
        });
    }

    public function closeRFQ(RFQ $rfq): void
    {
        DB::transaction(function () use ($rfq) {
            $rfq->update(['status' => 'closed']);
        });
    }

    public function submitResponse(RFQ $rfq, Vendor $vendor, array $data): RFQResponse
    {
        if (!$rfq->canReceiveResponses()) {
            throw new DomainException('RFQ is not accepting responses.');
        }

        $pivot = $rfq->vendors()->where('vendor_id', $vendor->id)->first();
        if (!$pivot) {
            throw new DomainException('Vendor not invited to this RFQ.');
        }

        return DB::transaction(function () use ($rfq, $vendor, $data) {
            $response = RFQResponse::create([
                'rfq_id' => $rfq->id,
                'vendor_id' => $vendor->id,
                'quoted_amount' => $data['quoted_amount'],
                'delivery_time_days' => $data['delivery_time_days'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            $rfq->vendors()->updateExistingPivot($vendor->id, [
                'status' => 'responded',
                'responded_at' => now(),
            ]);

            return $response;
        });
    }

    public function evaluateResponse(RFQResponse $response, User $evaluator, array $evaluation): RFQEvaluation
    {
        $criteriaScores = $evaluation['criteria_scores'] ?? [];
        $weights = $response->rfq->evaluation_criteria['weights'] ?? [];

        $totalScore = $this->calculateWeightedScore($criteriaScores, $weights);

        $evaluation = RFQEvaluation::create([
            'rfq_response_id' => $response->id,
            'evaluator_id' => $evaluator->id,
            'criteria_scores' => $criteriaScores,
            'comments' => $evaluation['comments'] ?? null,
            'total_score' => $totalScore,
        ]);

        // Update response-level scores if provided
        $response->update([
            'technical_score' => $criteriaScores['technical'] ?? $response->technical_score,
            'commercial_score' => $criteriaScores['commercial'] ?? $response->commercial_score,
            'total_score' => $totalScore,
        ]);

        return $evaluation;
    }

    public function awardContract(RFQ $rfq, RFQResponse $winningResponse): void
    {
        DB::transaction(function () use ($rfq, $winningResponse) {
            // Update RFQ status
            $rfq->update(['status' => 'awarded']);

            // Accept winner, reject others
            $rfq->responses()->where('id', $winningResponse->id)->update(['status' => 'accepted']);
            $rfq->responses()->where('id', '!=', $winningResponse->id)->update(['status' => 'rejected']);

            // Update pivot statuses
            $rfq->vendors()->updateExistingPivot($winningResponse->vendor_id, [
                'status' => 'awarded',
                'awarded_at' => now(),
            ]);

            $loserVendorIds = $rfq->responses()
                ->where('id', '!=', $winningResponse->id)
                ->pluck('vendor_id')
                ->all();

            foreach ($loserVendorIds as $vid) {
                $rfq->vendors()->updateExistingPivot($vid, ['status' => 'lost']);
            }
        });
    }

    private function calculateWeightedScore(array $scores, array $weights): float
    {
        // Default equal weights if not provided
        if (empty($weights)) {
            $weights = [];
            foreach ($scores as $k => $_) {
                $weights[$k] = 1 / max(1, count($scores));
            }
        }

        // Normalize weights to sum to 1
        $sum = array_sum($weights);
        if ($sum <= 0) {
            return 0.0;
        }
        $normalized = array_map(fn($w) => $w / $sum, $weights);

        $total = 0.0;
        foreach ($normalized as $criteria => $weight) {
            $value = isset($scores[$criteria]) ? (float)$scores[$criteria] : 0.0;
            $total += $weight * $value;
        }
        return $total;
    }
}