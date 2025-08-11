<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorReviewResource\Pages;
use App\Filament\Resources\VendorReviewResource\RelationManagers;
use App\Models\VendorReview;
use App\Models\Vendor;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VendorReviewResource extends Resource
{
    protected static ?string $model = VendorReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Vendor Management';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Review Information')
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'company_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Select vendor to review'),

                        Forms\Components\Select::make('reviewer_id')
                            ->label('Reviewer')
                            ->relationship('reviewer', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Select reviewer'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Ratings')
                    ->description('Rate each aspect from 1 (Poor) to 5 (Excellent)')
                    ->schema([
                        Forms\Components\Select::make('rating_quality')
                            ->label('Quality Rating')
                            ->options([
                                1 => '1 - Poor',
                                2 => '2 - Fair',
                                3 => '3 - Good',
                                4 => '4 - Very Good',
                                5 => '5 - Excellent',
                            ])
                            ->required()
                            ->placeholder('Select quality rating'),

                        Forms\Components\Select::make('rating_timeliness')
                            ->label('Timeliness Rating')
                            ->options([
                                1 => '1 - Poor',
                                2 => '2 - Fair',
                                3 => '3 - Good',
                                4 => '4 - Very Good',
                                5 => '5 - Excellent',
                            ])
                            ->required()
                            ->placeholder('Select timeliness rating'),

                        Forms\Components\Select::make('rating_communication')
                            ->label('Communication Rating')
                            ->options([
                                1 => '1 - Poor',
                                2 => '2 - Fair',
                                3 => '3 - Good',
                                4 => '4 - Very Good',
                                5 => '5 - Excellent',
                            ])
                            ->required()
                            ->placeholder('Select communication rating'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Comments')
                    ->schema([
                        Forms\Components\Textarea::make('comments')
                            ->label('Review Comments')
                            ->maxLength(1000)
                            ->rows(4)
                            ->placeholder('Provide detailed feedback about the vendor...'),
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

                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Reviewer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating_quality')
                    ->label('Quality')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '5' => 'success',
                        '4' => 'primary',
                        '3' => 'warning',
                        '1', '2' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating_timeliness')
                    ->label('Timeliness')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '5' => 'success',
                        '4' => 'primary',
                        '3' => 'warning',
                        '1', '2' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating_communication')
                    ->label('Communication')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '5' => 'success',
                        '4' => 'primary',
                        '3' => 'warning',
                        '1', '2' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('average_rating')
                    ->label('Avg Rating')
                    ->getStateUsing(fn (VendorReview $record): float => 
                        round(($record->rating_quality + $record->rating_timeliness + $record->rating_communication) / 3, 2)
                    )
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'primary',
                        $state >= 2.5 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('comments')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Review Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vendor')
                    ->relationship('vendor', 'company_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('reviewer')
                    ->relationship('reviewer', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('high_ratings')
                    ->query(fn (Builder $query): Builder => $query->where(function($query) {
                        $query->where('rating_quality', '>=', 4)
                              ->where('rating_timeliness', '>=', 4)
                              ->where('rating_communication', '>=', 4);
                    }))
                    ->label('High Ratings (4+)'),

                Tables\Filters\Filter::make('low_ratings')
                    ->query(fn (Builder $query): Builder => $query->where(function($query) {
                        $query->where('rating_quality', '<=', 2)
                              ->orWhere('rating_timeliness', '<=', 2)
                              ->orWhere('rating_communication', '<=', 2);
                    }))
                    ->label('Low Ratings (2-)'),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorReviews::route('/'),
            'create' => Pages\CreateVendorReview::route('/create'),
            'view' => Pages\ViewVendorReview::route('/{record}'),
            'edit' => Pages\EditVendorReview::route('/{record}/edit'),
        ];
    }
}
