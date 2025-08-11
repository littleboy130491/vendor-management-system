<?php

namespace App\Filament\Resources\RFQResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VendorsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendors';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vendor_id')
                    ->relationship('vendor', 'company_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DateTimePicker::make('invited_at'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('vendor.company_name')
            ->columns([
                Tables\Columns\TextColumn::make('vendor.company_name')
                    ->label('Vendor'),
                Tables\Columns\TextColumn::make('vendor.contact_email')
                    ->label('Email'),
                Tables\Columns\BadgeColumn::make('pivot.status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'invited',
                        'primary' => 'responded',
                        'success' => 'awarded',
                        'danger' => 'lost',
                    ]),
                Tables\Columns\TextColumn::make('pivot.invited_at')
                    ->label('Invited At')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('pivot.responded_at')
                    ->label('Responded At')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}