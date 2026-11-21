<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Pages\ImportStudents;
use App\Filament\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // wf_S17: [ CSV Import <= 500 rows ] beside [ + Enrol ].
            Actions\Action::make('import')
                ->label('CSV import')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->url(ImportStudents::getUrl()),

            Actions\CreateAction::make()
                ->using(function (array $data): User {
                    $data = UserResource::forceSchool($data);

                    // class_id belongs on the student profile, not the users table.
                    $classId = Arr::pull($data, 'class_id');

                    // No password typed: the account gets one nobody knows, and the
                    // welcome email lets its owner choose a real one (UC-18).
                    $welcome = blank($data['password'] ?? null);
                    if ($welcome) {
                        $data['password'] = Hash::make(Str::random(40));
                    }

                    $record = DB::transaction(function () use ($data, $classId): User {
                        $record = User::create($data);
                        UserResource::syncStudentClass($record, $classId);

                        // FR-10.8: an account creation is an auditable event.
                        AuditLog::record(auth()->id(), 'user.created', "user:{$record->id}", [
                            'role' => $record->role->value,
                        ]);

                        return $record;
                    });

                    if ($welcome) {
                        $record->notify(new WelcomeNotification);
                    }

                    return $record;
                }),
        ];
    }
}
