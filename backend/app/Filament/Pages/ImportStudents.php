<?php

namespace App\Filament\Pages;

use App\Filament\AdminScope;
use App\Models\School;
use App\Services\StudentImportService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * UC-18, wf_S17: [ CSV Import <= 500 rows ]. One bad row and nothing is created,
 * and every bad row is listed with its line number (FR-10.3).
 */
class ImportStudents extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'People';

    protected static ?string $navigationLabel = 'Import students';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Import students from a CSV file';

    protected static string $view = 'filament.pages.import-students';

    public ?array $data = [];

    /** @var list<array{line:int, messages:list<string>}> */
    public array $rowErrors = [];

    public ?int $created = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                // The team works across schools, so it says which one; a school
                // administrator's import always goes to its own (SR-04).
                Forms\Components\Select::make('school_id')
                    ->label('School')
                    ->options(fn () => School::orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->visible(fn () => AdminScope::isTeam()),

                Forms\Components\FileUpload::make('file')
                    ->label('CSV file')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', '.csv'])
                    ->maxSize(1024)
                    ->storeFiles(false)
                    ->required()
                    ->helperText('First row: name,email,class. Up to 500 students. The class must already exist, spelt as it is under Classes. Excel: Save As, "CSV UTF-8".'),
            ]);
    }

    public function import(StudentImportService $service): void
    {
        $state = $this->form->getState();

        $schoolId = AdminScope::schoolId() ?? (int) ($state['school_id'] ?? 0);
        $school = School::find($schoolId);
        abort_unless($school, 403);

        // Only a file uploaded through this form is read. A path typed into the
        // component's state instead would otherwise be read from the server's disk,
        // and its lines echoed back in the error list.
        $file = $state['file'] ?? null;
        abort_unless($file instanceof TemporaryUploadedFile, 422, 'Upload the CSV file again.');
        $contents = (string) $file->get();

        $result = $service->import($school, $contents, AdminScope::user());

        $this->rowErrors = $result['errors'];
        $this->created = $result['errors'] === [] ? $result['created'] : null;

        if ($result['errors'] === []) {
            $this->form->fill();

            Notification::make()
                ->title("{$result['created']} students added to {$school->name}")
                ->body('Each one has been sent a welcome email with a link to choose a password.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Nothing was imported')
                ->body(count($result['errors']).' row(s) need fixing. Correct the file and upload it again.')
                ->danger()
                ->send();
        }
    }
}
