<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RFQResource\Pages;
use App\Filament\Resources\RFQResource\RelationManagers;
use App\Models\RFQ;
use App\Models\User;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RFQResource extends Resource
{
    protected static ?string $model = RFQ::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Procurement';
    protected static ?string $navigationLabel = 'RFQs';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                if ($operation !== 'create') {
                                    return;
                                }
                                $set('slug', \Illuminate\Support\Str::slug($state));
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('created_by')
                            ->label('Created By')
                            ->relationship('creator', 'name')
                            ->required()
                            ->default(auth()->id())
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Schedule & Budget')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Start Date')
                            ->required()
                            ->minDate(now()),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('End Date')
                            ->required()
                            ->after('starts_at'),
                        Forms\Components\TextInput::make('budget')
                            ->label('Budget')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                                'GBP' => 'GBP',
                            ])
                            ->default('USD'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'closed' => 'Closed',
                                'awarded' => 'Awarded',
                            ])
                            ->default('draft')
                            ->required(),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Published At'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\KeyValue::make('scope')
                            ->label('Scope/Requirements')
                            ->keyLabel('Requirement')
                            ->valueLabel('Description'),
                        Forms\Components\KeyValue::make('evaluation_criteria')
                            ->label('Evaluation Criteria')
                            ->keyLabel('Criterion')
                            ->valueLabel('Weight (%)'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Invited Vendors')
                    ->schema([
                        Forms\Components\Select::make('vendors')
                            ->label('Invite Vendors')
                            ->multiple()
                            ->relationship('vendors', 'company_name')
                            ->options(Vendor::where('status', 'active')->pluck('company_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->helperText('Select vendors to invite for this RFQ'),
                    ])
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'primary' => 'published',
                        'warning' => 'closed',
                        'success' => 'awarded',
                    ]),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Start Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('End Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('budget')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('responses_count')
                    ->label('Responses')
                    ->counts('responses')
                    ->badge(),
                Tables\Columns\TextColumn::make('vendors_count')
                    ->label('Invited')
                    ->counts('vendors')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'closed' => 'Closed',
                        'awarded' => 'Awarded',
                    ]),
                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query): Builder => $query->where('ends_at', '>', now()))
                    ->label('Active RFQs'),
                Tables\Filters\Filter::make('budget')
                    ->form([
                        Forms\Components\TextInput::make('budget_from')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('budget_to')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['budget_from'],
                                fn (Builder $query, $amount): Builder => $query->where('budget', '>=', $amount),
                            )
                            ->when(
                                $data['budget_to'],
                                fn (Builder $query, $amount): Builder => $query->where('budget', '<=', $amount),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('publish')
                    ->action(function (RFQ $record) {
                        $record->update(['status' => 'published', 'published_at' => now()]);
                    })
                    ->icon('heroicon-o-megaphone')
                    ->color('primary')
                    ->visible(fn (RFQ $record) => $record->status === 'draft'),
                Tables\Actions\Action::make('close')
                    ->action(function (RFQ $record) {
                        $record->update(['status' => 'closed']);
                    })
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->visible(fn (RFQ $record) => $record->status === 'published'),
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
            RelationManagers\ResponsesRelationManager::class,
            RelationManagers\VendorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRFQs::route('/'),
            'create' => Pages\CreateRFQ::route('/create'),
            'view' => Pages\ViewRFQ::route('/{record}'),
            'edit' => Pages\EditRFQ::route('/{record}/edit'),
        ];
    }
}