<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\StudentProfile;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->after(function (Model $record) {
                    /** @var User $record */
                    if ($record->isStudent()) {
                        StudentProfile::firstOrCreate(['user_id' => $record->id]);
                    }

                    // FR-10.8: an account creation is an auditable event.
                    AuditLog::record(auth()->id(), 'user.created', "user:{$record->id}", [
                        'role' => $record->role->value,
                    ]);
                }),
        ];
    }
}
