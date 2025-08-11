<?php

namespace App\Filament\Resources\RFQEvaluationResource\Pages;

use App\Filament\Resources\RFQEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRFQEvaluation extends EditRecord
{
    protected static string $resource = RFQEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
