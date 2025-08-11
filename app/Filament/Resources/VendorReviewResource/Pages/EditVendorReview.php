<?php

namespace App\Filament\Resources\VendorReviewResource\Pages;

use App\Filament\Resources\VendorReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendorReview extends EditRecord
{
    protected static string $resource = VendorReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
