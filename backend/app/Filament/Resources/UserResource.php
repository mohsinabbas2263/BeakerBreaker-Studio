<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\AuditLog;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

/**
 * FR-01.5 and FR-10.1: the only place an account comes into existence.
 * FR-01.6 holds because this panel is the administrator's, and the API has no
 * equivalent route.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'People';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('school_id')
                ->relationship('school', 'name')
                ->label('School')
                ->searchable()
                ->preload()
                ->live(),

            // live(): the class picker below appears only once Student is chosen.
            Forms\Components\Select::make('role')
                ->options([
                    UserRole::Student->value => 'Student',
                    UserRole::Teacher->value => 'Teacher',
                    UserRole::Administrator->value => 'Administrator',
                ])
                ->required()
                ->native(false)
                ->live(),

            Forms\Components\TextInput::make('display_name')
                ->required()
                ->maxLength(60),

            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),

            // SR-01: hashed by the model cast. Left blank on edit, the stored
            // hash is kept rather than overwritten with an empty string.
            Forms\Components\TextInput::make('password')
                ->password()
                ->revealable()
                ->dehydrateStateUsing(fn (?string $state) => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn (?string $state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create')
                ->minLength(8)
                ->helperText('Leave blank to keep the current password.'),

            Forms\Components\Select::make('status')
                ->options(['active' => 'Active', 'suspended' => 'Suspended'])
                ->default('active')
                ->required()
                ->native(false)
                ->helperText('Suspending an account invalidates its tokens on the next request (SR-05).'),

            // FR-10.1: a student belongs to a class, which drives the class
            // leaderboard (FR-07.1) and the teacher's reach (FR-09.1). Saved onto
            // the student profile by ManageUsers, not onto the users table.
            Forms\Components\Select::make('class_id')
                ->label('Class (students only)')
                ->options(fn (Forms\Get $get) => SchoolClass::query()
                    ->when($get('school_id'), fn (Builder $q, $schoolId) => $q->where('school_id', $schoolId))
                    ->orderBy('title')
                    ->pluck('title', 'id'))
                ->searchable()
                ->visible(fn (Forms\Get $get) => self::isStudentRole($get('role'))),
        ]);
    }

    public static function isStudentRole(mixed $role): bool
    {
        return ($role instanceof UserRole ? $role->value : $role) === UserRole::Student->value;
    }

    /**
     * Writes the form's class choice onto the student's profile. Every student
     * gets a profile, with or without a class.
     */
    public static function syncStudentClass(User $user, mixed $classId): void
    {
        if (! $user->isStudent()) {
            return;
        }

        StudentProfile::updateOrCreate(
            ['user_id' => $user->id],
            ['class_id' => filled($classId) ? (int) $classId : null],
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('role')->badge()->sortable(),
                Tables\Columns\TextColumn::make('school.name')->label('School')->toggleable(),
                Tables\Columns\TextColumn::make('profile.total_points')->label('Points')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('last_login_at')->dateTime()->label('Last login')->toggleable(),
                Tables\Columns\TextColumn::make('deleted_at')->dateTime()->label('Deleted')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')->options([
                    'student' => 'Student', 'teacher' => 'Teacher', 'administrator' => 'Administrator',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    'active' => 'Active', 'suspended' => 'Suspended',
                ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, User $record): array {
                        $data['class_id'] = $record->profile?->class_id;

                        return $data;
                    })
                    ->using(function (User $record, array $data): User {
                        $classId = Arr::pull($data, 'class_id');
                        $record->update($data);
                        self::syncStudentClass($record, $classId);

                        // FR-10.8: every administrative account operation is recorded.
                        AuditLog::record(auth()->id(), 'user.updated', "user:{$record->id}");

                        return $record;
                    }),
                // FR-01.5: deletion is a soft delete, recoverable for 30 days.
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->defaultSort('display_name');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            \Illuminate\Database\Eloquent\SoftDeletingScope::class,
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageUsers::route('/')];
    }

}
