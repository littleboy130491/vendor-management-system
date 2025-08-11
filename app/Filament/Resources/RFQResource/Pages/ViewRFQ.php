<?php

namespace App\Filament\Resources\RFQResource\Pages;

use App\Filament\Resources\RFQResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRFQ extends ViewRecord
{
    protected static string $resource = RFQResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}