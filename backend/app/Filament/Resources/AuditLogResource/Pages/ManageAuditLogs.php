<?php

namespace App\Filament\Resources\AuditLogResource\Pages;

use App\Filament\Resources\AuditLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAuditLogs extends ManageRecords
{
    protected static string $resource = AuditLogResource::class;

    /** Insert only: there is no create action here by design. */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
