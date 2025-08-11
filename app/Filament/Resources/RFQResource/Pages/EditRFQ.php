<?php

namespace App\Filament\Resources\RFQResource\Pages;

use App\Filament\Resources\RFQResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRFQ extends EditRecord
{
    protected static string $resource = RFQResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}