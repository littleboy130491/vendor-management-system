<?php

namespace App\Filament\Resources\VendorWarningResource\Pages;

use App\Filament\Resources\VendorWarningResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVendorWarning extends ViewRecord
{
    protected static string $resource = VendorWarningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}