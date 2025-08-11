<?php

namespace App\Filament\Resources\RFQResource\Pages;

use App\Filament\Resources\RFQResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRFQs extends ListRecords
{
    protected static string $resource = RFQResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}