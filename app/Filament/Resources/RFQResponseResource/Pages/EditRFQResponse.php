<?php

namespace App\Filament\Resources\RFQResponseResource\Pages;

use App\Filament\Resources\RFQResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRFQResponse extends EditRecord
{
    protected static string $resource = RFQResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}