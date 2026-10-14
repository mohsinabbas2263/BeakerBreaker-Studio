<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use App\Filament\Resources\CertificateResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCertificates extends ManageRecords
{
    protected static string $resource = CertificateResource::class;

    /** Insert only: there is no create action here by design. */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
