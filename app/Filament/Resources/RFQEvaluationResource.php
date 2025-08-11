<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RFQEvaluationResource\Pages;
use App\Models\RFQEvaluation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RFQEvaluationResource extends Resource
{
    protected static ?string $model = RFQEvaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Procurement';
    protected static ?string $navigationLabel = 'RFQ Evaluations';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Evaluation Details')
                    ->schema([
                        Forms\Components\Select::make('rfq_response_id')
                            ->label('RFQ Response')
                            ->relationship('response', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->vendor->business_name} - {$record->rfq->title}")
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('evaluator_id')
                            ->label('Evaluator')
                            ->relationship('evaluator', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('total_score')
                            ->label('Total Score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                        Forms\Components\KeyValue::make('criteria_scores')
                            ->label('Criteria Scores')
                            ->keyLabel('Criteria')
                            ->valueLabel('Score'),
                        Forms\Components\Textarea::make('comments')
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('response.rfq.title')
                    ->label('RFQ')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('response.vendor.business_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('evaluator.name')
                    ->label('Evaluator')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_score')
                    ->label('Total Score')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('evaluator')
                    ->relationship('evaluator', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRFQEvaluations::route('/'),
            'create' => Pages\CreateRFQEvaluation::route('/create'),
            'view' => Pages\ViewRFQEvaluation::route('/{record}'),
            'edit' => Pages\EditRFQEvaluation::route('/{record}/edit'),
        ];
    }
}