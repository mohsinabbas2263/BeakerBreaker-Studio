<?php

namespace App\Filament\Resources;

use App\Filament\AdminScope;
use App\Filament\Resources\SchoolClassResource\Pages;
use App\Models\AuditLog;
use App\Models\SchoolClass;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * FR-10.1: classes, and the teachers assigned to them.
 * FR-09.1 depends on this assignment: a teacher reaches a student only through it.
 * FR-10.4: which experiments are turned off for a class.
 * SR-04: a school administrator sees and changes its own school's classes only.
 */
class SchoolClassResource extends Resource
{
    protected static ?string $model = SchoolClass::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'People';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'class';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('school_id')
                ->relationship('school', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->visible(fn () => AdminScope::isTeam()),

            Forms\Components\TextInput::make('title')->required()->maxLength(60),

            Forms\Components\Select::make('grade_level')
                ->options(\App\Models\School::GRADES)
                ->required()
                ->native(false),

            Forms\Components\TextInput::make('academic_year')
                ->required()
                ->placeholder('2026-2027')
                ->maxLength(9),

            // FR-09.1: only a teacher of the class's own school can be assigned.
            Forms\Components\Select::make('teachers')
                ->relationship('teachers', 'display_name', fn (Builder $query, Forms\Get $get) => $query
                    ->where('role', 'teacher')
                    ->where('school_id', AdminScope::schoolId() ?? $get('school_id')))
                ->multiple()
                ->preload()
                ->searchable()
                ->helperText('Only an assigned teacher may act on this class (FR-09.1).'),
        ]);
    }

    /** SR-04: a school administrator's classes belong to its school, whatever the form sent. */
    public static function forceSchool(array $data): array
    {
        if (AdminScope::schoolId() !== null) {
            $data['school_id'] = AdminScope::schoolId();
        }

        return $data;
    }

    /**
     * FR-09.1 and SR-04: whatever a request carried, a class keeps only teachers
     * of its own school, so no teacher reaches another school's students.
     */
    public static function dropForeignTeachers(SchoolClass $record): void
    {
        $foreign = $record->teachers()
            ->where(fn (Builder $q) => $q->where('role', '!=', 'teacher')
                ->orWhereNull('school_id')
                ->orWhere('school_id', '!=', $record->school_id))
            ->pluck('users.id');

        if ($foreign->isNotEmpty()) {
            $record->teachers()->detach($foreign);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('school.name')->label('School')->searchable()
                    ->visible(fn () => AdminScope::isTeam()),
                Tables\Columns\TextColumn::make('grade_level')->label('Grade')->sortable(),
                Tables\Columns\TextColumn::make('academic_year')->label('Year'),
                Tables\Columns\TextColumn::make('student_profiles_count')->counts('studentProfiles')->label('Students'),
                Tables\Columns\TextColumn::make('teachers.display_name')->label('Teachers')->badge(),
                Tables\Columns\TextColumn::make('disabled_experiments_count')->counts('disabledExperiments')
                    ->label('Experiments off')
                    ->color(fn (int $state) => $state > 0 ? 'warning' : null),
            ])
            ->actions([
                self::experimentsAction(),
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(fn (array $data) => self::forceSchool($data))
                    ->after(fn (SchoolClass $record) => self::dropForeignTeachers($record)),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    /**
     * FR-10.4: tick the experiments this class may use. Unticking one turns it off
     * at once: it leaves the students' library, cannot be opened by address, and
     * cannot be set as a task. Ticking it again brings it back.
     */
    public static function experimentsAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('experiments')
            ->label('Experiments')
            ->icon('heroicon-o-beaker')
            ->modalHeading(fn (SchoolClass $record) => "Experiments for {$record->title}")
            ->modalDescription('Untick an experiment to turn it off for this class. The change applies straight away.')
            ->fillForm(fn (SchoolClass $record) => [
                'enabled' => $record->licensedExperiments()
                    ->whereNotIn('id', $record->disabledExperiments()->select('experiments.id'))
                    ->pluck('id')
                    ->all(),
            ])
            ->form(fn (SchoolClass $record) => [
                Forms\Components\CheckboxList::make('enabled')
                    ->label('Turned on')
                    ->options($record->licensedExperiments()
                        ->orderBy('grade_level')->orderBy('title')
                        ->get()
                        ->mapWithKeys(fn ($e) => [$e->id => "Class {$e->grade_level}: {$e->title}"]))
                    ->bulkToggleable()
                    ->columns(2),
            ])
            ->action(function (SchoolClass $record, array $data): void {
                $licensed = $record->licensedExperiments()->pluck('id');
                $enabled = collect($data['enabled'] ?? [])->map(fn ($id) => (int) $id);
                $off = $licensed->diff($enabled)->values();

                $record->disabledExperiments()->sync(
                    $off->mapWithKeys(fn (int $id) => [$id => [
                        'disabled_by_user_id' => auth()->id(),
                        'disabled_at' => now(),
                    ]])->all()
                );

                AuditLog::record(auth()->id(), 'class.experiments_changed', "class:{$record->id}", [
                    'disabled' => $off->all(),
                ]);

                Notification::make()
                    ->title($off->isEmpty() ? 'Every experiment is on for this class.' : "{$off->count()} experiment(s) off for this class.")
                    ->success()
                    ->send();
            });
    }

    public static function getEloquentQuery(): Builder
    {
        return AdminScope::bySchoolColumn(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSchoolClasses::route('/')];
    }
}
