<?php

namespace App\Filament\Resources\RFQEvaluationResource\Pages;

use App\Filament\Resources\RFQEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRFQEvaluations extends ListRecords
{
    protected static string $resource = RFQEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
