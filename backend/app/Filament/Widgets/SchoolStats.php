<?php

namespace App\Filament\Widgets;

use App\Filament\AdminScope;
use App\Models\Certificate;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\SchoolReportService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** wf_S16, School Stats: Students, Teachers, Classes, Certified %. SR-04: own school only. */
class SchoolStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $users = fn (string $role) => AdminScope::bySchoolColumn(
            User::query()->where('role', $role)->where('status', 'active')
        );

        $students = $users('student')->count();
        $certified = Certificate::whereIn('user_id', $users('student')->select('id'))->distinct()->count('user_id');
        $rate = SchoolReportService::percent($certified, $students);

        return [
            Stat::make('Students', $students),
            Stat::make('Teachers', $users('teacher')->count()),
            Stat::make('Classes', AdminScope::bySchoolColumn(SchoolClass::query())->count()),
            Stat::make('Certified', $rate === null ? 'No data' : $rate.'%')
                ->description("{$certified} hold a safety certificate"),
        ];
    }
}
