<?php

namespace App\Filament\Pages;

use App\Filament\AdminScope;
use App\Models\AuditLog;
use App\Models\School;
use App\Services\SchoolReportService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * wf_S18, School Reports: FR-10.6 analytics and the FR-10.7 export.
 * A school administrator sees its own school; the team picks one or all.
 */
class SchoolReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'School report';

    protected static ?string $title = 'School report';

    protected static string $view = 'filament.pages.school-reports';

    /** Team only: which school to report on; null means every school. */
    public ?int $schoolId = null;

    public function mount(): void
    {
        $this->schoolId = AdminScope::schoolId();
    }

    public function updatedSchoolId(): void
    {
        // SR-04: a school administrator cannot point the page at another school.
        if (! AdminScope::isTeam()) {
            $this->schoolId = AdminScope::schoolId();
        }
    }

    public function school(): ?School
    {
        $id = AdminScope::schoolId() ?? $this->schoolId;

        return $id ? School::find($id) : null;
    }

    public function getViewData(): array
    {
        return [
            'report' => app(SchoolReportService::class)->build($this->school()),
            'schools' => AdminScope::isTeam() ? School::orderBy('name')->pluck('name', 'id') : collect(),
        ];
    }

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
        $service = app(SchoolReportService::class);
        $school = $this->school();
        $csv = $service->toCsv($service->build($school));

        // FR-10.8: every report export is recorded.
        AuditLog::record(auth()->id(), 'report.exported', $school ? "school:{$school->id}" : 'school:all', [
            'format' => 'csv',
        ]);

        $name = 'school-report-'.str($school?->name ?? 'all-schools')->slug().'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(fn () => print ($csv), $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
