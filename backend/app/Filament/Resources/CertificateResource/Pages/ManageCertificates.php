<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use App\Filament\Resources\CertificateResource;
use App\Models\AuditLog;
use App\Services\SchoolReportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManageCertificates extends ManageRecords
{
    protected static string $resource = CertificateResource::class;

    /** Insert only: there is no create action here by design. FR-10.5: the register exports. */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        // The resource query is already limited to the administrator's school (SR-04).
        $certificates = CertificateResource::getEloquentQuery()
            ->with(['user' => fn ($q) => $q->withTrashed(), 'user.profile.schoolClass'])
            ->orderBy('issued_on')
            ->get();

        $csv = app(SchoolReportService::class)->certificatesCsv($certificates);

        // FR-10.8: an export of the register is recorded.
        AuditLog::record(auth()->id(), 'certificates.exported', 'certificates', [
            'count' => $certificates->count(),
            'format' => 'csv',
        ]);

        return response()->streamDownload(
            fn () => print ($csv),
            'certificate-register-'.now()->format('Y-m-d').'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}
