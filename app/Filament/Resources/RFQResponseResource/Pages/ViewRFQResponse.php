<?php

namespace App\Filament\Resources\RFQResponseResource\Pages;

use App\Filament\Resources\RFQResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRFQResponse extends ViewRecord
{
    protected static string $resource = RFQResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}