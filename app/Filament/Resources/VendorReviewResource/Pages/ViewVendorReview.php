<?php

namespace App\Filament\Resources\VendorReviewResource\Pages;

use App\Filament\Resources\VendorReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVendorReview extends ViewRecord
{
    protected static string $resource = VendorReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}