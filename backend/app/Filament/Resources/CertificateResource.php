<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * FR-02.8: issued certificates are read only, here as everywhere else.
 * The resource declares no form, no create action and no delete action, so the
 * panel cannot offer what the model would refuse.
 */
class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Safety';

    protected static bool $canCreateAnother = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('certificate_reference')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('user.display_name')->label('Student')->searchable(),
                Tables\Columns\TextColumn::make('tier1_score')->label('T1'),
                Tables\Columns\TextColumn::make('tier2_score')->label('T2'),
                Tables\Columns\TextColumn::make('tier3_score')->label('T3'),
                Tables\Columns\TextColumn::make('tier4_score')->label('T4'),
                Tables\Columns\TextColumn::make('issued_on')->date()->sortable(),
            ])
            ->defaultSort('issued_on', 'desc')
            ->actions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCertificates::route('/')];
    }
}
