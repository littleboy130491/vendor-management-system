<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RFQResponseResource\Pages;
use App\Models\RFQResponse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RFQResponseResource extends Resource
{
    protected static ?string $model = RFQResponse::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Procurement';
    protected static ?string $navigationLabel = 'RFQ Responses';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Response Details')
                    ->schema([
                        Forms\Components\Select::make('rfq_id')
                            ->label('RFQ')
                            ->relationship('rfq', 'title')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'company_name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'submitted' => 'Submitted',
                                'accepted' => 'Accepted',
                                'rejected' => 'Rejected',
                                'withdrawn' => 'Withdrawn',
                            ])
                            ->default('submitted')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Proposal')
                    ->schema([
                        Forms\Components\TextInput::make('quoted_amount')
                            ->label('Quoted Amount')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\TextInput::make('delivery_time_days')
                            ->label('Delivery Time (days)')
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(6)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Submission Info')
                    ->schema([
                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->label('Submitted At')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rfq.title')
                    ->label('RFQ')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('vendor.company_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quoted_amount')
                    ->label('Quote')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_time_days')
                    ->label('Delivery (days)')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'submitted',
                        'success' => 'accepted',
                        'danger' => 'rejected',
                        'secondary' => 'withdrawn',
                    ]),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'submitted' => 'Submitted',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        'withdrawn' => 'Withdrawn',
                    ]),
                Tables\Filters\SelectFilter::make('rfq')
                    ->relationship('rfq', 'title')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('vendor')
                    ->relationship('vendor', 'company_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('amount_to')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('quoted_amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('quoted_amount', '<=', $amount),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('withdraw')
                    ->action(function (RFQResponse $record) {
                        $record->update(['status' => 'withdrawn']);
                    })
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('secondary')
                    ->visible(fn (RFQResponse $record) => in_array($record->status, ['submitted'])),
                Tables\Actions\Action::make('accept')
                    ->action(function (RFQResponse $record) {
                        $record->update(['status' => 'accepted']);
                    })
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (RFQResponse $record) => in_array($record->status, ['submitted'])),
                Tables\Actions\Action::make('reject')
                    ->action(function (RFQResponse $record) {
                        $record->update(['status' => 'rejected']);
                    })
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (RFQResponse $record) => in_array($record->status, ['submitted'])),
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
            'index' => Pages\ListRFQResponses::route('/'),
            'create' => Pages\CreateRFQResponse::route('/create'),
            'view' => Pages\ViewRFQResponse::route('/{record}'),
            'edit' => Pages\EditRFQResponse::route('/{record}/edit'),
        ];
    }
}