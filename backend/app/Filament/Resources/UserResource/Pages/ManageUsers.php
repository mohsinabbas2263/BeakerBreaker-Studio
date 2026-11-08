<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Arr;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->using(function (array $data): User {
                    // class_id belongs on the student profile, not the users table.
                    $classId = Arr::pull($data, 'class_id');

                    $record = User::create($data);
                    UserResource::syncStudentClass($record, $classId);

                    // FR-10.8: an account creation is an auditable event.
                    AuditLog::record(auth()->id(), 'user.created', "user:{$record->id}", [
                        'role' => $record->role->value,
                    ]);

                    return $record;
                }),
        ];
    }
}
