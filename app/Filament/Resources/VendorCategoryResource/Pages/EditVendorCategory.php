<?php

namespace App\Filament\Resources\VendorCategoryResource\Pages;

use App\Filament\Resources\VendorCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendorCategory extends EditRecord
{
    protected static string $resource = VendorCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
