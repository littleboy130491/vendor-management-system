<?php

namespace App\Filament\Resources\VendorWarningResource\Pages;

use App\Filament\Resources\VendorWarningResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendorWarning extends EditRecord
{
    protected static string $resource = VendorWarningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
