<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** FR-10.8: the permanent record. Readable, never writable, retained 7 years. */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $modelLabel = 'audit entry';

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
                Tables\Columns\TextColumn::make('occurred_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('actor.display_name')->label('Actor')->searchable(),
                Tables\Columns\TextColumn::make('action_type')->badge()->searchable(),
                Tables\Columns\TextColumn::make('target_reference')->searchable(),
                Tables\Columns\TextColumn::make('ip_address')->label('IP')->toggleable(),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->actions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageAuditLogs::route('/')];
    }
}
