<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorWarningResource\Pages;
use App\Filament\Resources\VendorWarningResource\RelationManagers;
use App\Models\VendorWarning;
use App\Models\Vendor;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VendorWarningResource extends Resource
{
    protected static ?string $model = VendorWarning::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'Vendor Management';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Warning Information')
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'company_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Select vendor'),

                        Forms\Components\Select::make('issued_by')
                            ->label('Issued By')
                            ->relationship('issuer', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Select user who issued the warning'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Warning Details')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Warning Type')
                            ->options([
                                'quality' => 'Quality Issue',
                                'timeliness' => 'Timeliness Issue',
                                'communication' => 'Communication Issue',
                                'compliance' => 'Compliance Issue',
                                'financial' => 'Financial Issue',
                                'security' => 'Security Issue',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->placeholder('Select warning type'),

                        Forms\Components\Textarea::make('details')
                            ->label('Warning Details')
                            ->required()
                            ->maxLength(1000)
                            ->rows(4)
                            ->placeholder('Describe the issue that prompted this warning...')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Resolution')
                    ->schema([
                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->label('Resolved At')
                            ->placeholder('Select resolution date & time'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vendor.company_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'quality' => 'primary',
                        'timeliness' => 'warning',
                        'communication' => 'info',
                        'compliance' => 'danger',
                        'financial' => 'success',
                        'security' => 'danger',
                        'other' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'quality' => 'Quality Issue',
                        'timeliness' => 'Timeliness Issue',
                        'communication' => 'Communication Issue',
                        'compliance' => 'Compliance Issue',
                        'financial' => 'Financial Issue',
                        'security' => 'Security Issue',
                        'other' => 'Other',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('details')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_resolved')
                    ->label('Resolved')
                    ->getStateUsing(fn (VendorWarning $record): bool => !is_null($record->resolved_at))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('issuer.name')
                    ->label('Issued By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Resolved At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vendor')
                    ->relationship('vendor', 'company_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'quality' => 'Quality Issue',
                        'timeliness' => 'Timeliness Issue',
                        'communication' => 'Communication Issue',
                        'compliance' => 'Compliance Issue',
                        'financial' => 'Financial Issue',
                        'security' => 'Security Issue',
                        'other' => 'Other',
                    ]),

                Tables\Filters\Filter::make('unresolved')
                    ->query(fn (Builder $query): Builder => $query->whereNull('resolved_at'))
                    ->label('Unresolved Warnings'),

                Tables\Filters\Filter::make('resolved')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('resolved_at'))
                    ->label('Resolved Warnings'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Warning as Resolved')
                    ->modalDescription('Are you sure you want to mark this warning as resolved?')
                    ->action(fn (VendorWarning $record) => $record->update(['resolved_at' => now()]))
                    ->visible(fn (VendorWarning $record): bool => is_null($record->resolved_at)),
                Tables\Actions\Action::make('unresolve')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Warning as Unresolved')
                    ->modalDescription('Are you sure you want to mark this warning as unresolved?')
                    ->action(fn (VendorWarning $record) => $record->update(['resolved_at' => null]))
                    ->visible(fn (VendorWarning $record): bool => !is_null($record->resolved_at)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_resolved')
                        ->label('Mark as Resolved')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['resolved_at' => now()])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorWarnings::route('/'),
            'create' => Pages\CreateVendorWarning::route('/create'),
            'view' => Pages\ViewVendorWarning::route('/{record}'),
            'edit' => Pages\EditVendorWarning::route('/{record}/edit'),
        ];
    }
}
