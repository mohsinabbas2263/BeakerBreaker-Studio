<?php

namespace App\Filament\Resources\SchoolClassResource\Pages;

use App\Filament\Resources\SchoolClassResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSchoolClasses extends ManageRecords
{
    protected static string $resource = SchoolClassResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
