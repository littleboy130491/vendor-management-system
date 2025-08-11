<?php

namespace App\Filament\Resources\VendorReviewResource\Pages;

use App\Filament\Resources\VendorReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendorReviews extends ListRecords
{
    protected static string $resource = VendorReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
