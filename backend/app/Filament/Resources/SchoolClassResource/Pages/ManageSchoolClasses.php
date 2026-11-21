<?php

namespace App\Filament\Resources\SchoolClassResource\Pages;

use App\Filament\Resources\SchoolClassResource;
use App\Models\SchoolClass;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSchoolClasses extends ManageRecords
{
    protected static string $resource = SchoolClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(fn (array $data) => SchoolClassResource::forceSchool($data))
                ->after(fn (SchoolClass $record) => SchoolClassResource::dropForeignTeachers($record)),
        ];
    }
}
