<?php

namespace App\Filament\Resources\RFQResponseResource\Pages;

use App\Filament\Resources\RFQResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRFQResponses extends ListRecords
{
    protected static string $resource = RFQResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}