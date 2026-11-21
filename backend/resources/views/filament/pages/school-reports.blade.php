@php
    $pct = fn ($v) => $v === null ? 'No data' : rtrim(rtrim(number_format($v, 1), '0'), '.').'%';
    $r = $report['readiness'];
@endphp

<x-filament-panels::page>
    @if ($schools->isNotEmpty())
        <div style="max-width: 24rem">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="schoolId">
                    <option value="">All schools</option>
                    @foreach ($schools as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
    @endif

    <x-filament::section>
        <x-slot name="heading">{{ $report['school'] }}</x-slot>
        <x-slot name="description">Worked out just now, {{ $report['generated_at']->timezone('Asia/Karachi')->format('j M Y, g:i a') }} (Pakistan time).</x-slot>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap: 1rem">
            @foreach ([
                ['Certification rate', $pct($report['certification_rate']), $report['certified'].' of '.$report['students'].' students hold a safety certificate'],
                ['Completion rate', $pct($report['completion_rate']), 'of the experiments open to each student, completed at least once'],
                ['Students', $report['students'], $report['active_this_week'].' signed in during the last 7 days'],
                ['Teachers', $report['teachers'], $report['classes'].' '.str('class')->plural($report['classes'])],
            ] as [$label, $value, $note])
                <div style="border-radius: 18px; padding: 1rem; box-shadow: 0 8px 32px rgba(0,0,0,.10)" class="bg-white dark:bg-white/5">
                    <div style="font-size: .8rem; opacity: .7">{{ $label }}</div>
                    <div style="font-size: 1.8rem; font-weight: 800">{{ $value }}</div>
                    <div style="font-size: .75rem; opacity: .7">{{ $note }}</div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Readiness distribution</x-slot>
        <x-slot name="description">Each student's most recent readiness report (FR-08.3).</x-slot>

        @php
            $total = max(1, $r['ready'] + $r['developing'] + $r['not_ready'] + $r['none']);
            $bars = [
                ['Ready', $r['ready'], '#4CAF82'],
                ['Developing', $r['developing'], '#FFD54F'],
                ['Not ready', $r['not_ready'], '#FF8A50'],
                ['No report yet', $r['none'], '#CBD5E0'],
            ];
        @endphp

        <div style="display:flex; flex-direction:column; gap:.5rem">
            @foreach ($bars as [$label, $n, $colour])
                <div style="display:flex; align-items:center; gap:.75rem">
                    <div style="width: 7.5rem; font-size:.85rem">{{ $label }}</div>
                    <div style="flex:1; background: rgba(0,0,0,.06); border-radius: 9999px; height: .9rem; overflow:hidden">
                        <div style="width: {{ round(100 * $n / $total) }}%; background: {{ $colour }}; height:100%"></div>
                    </div>
                    <div style="width: 3rem; text-align:right; font-weight:700">{{ $n }}</div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">By class</x-slot>

        @if ($report['by_class'] === [])
            <p>No classes yet.</p>
        @else
            <div style="overflow-x:auto">
                <table style="width:100%; font-size:.875rem; border-collapse:collapse">
                    <thead>
                        <tr style="text-align:left">
                            <th style="padding:.5rem">Class</th>
                            @if ($schools->isNotEmpty())<th style="padding:.5rem">School</th>@endif
                            <th style="padding:.5rem">Students</th>
                            <th style="padding:.5rem">Experiments open</th>
                            <th style="padding:.5rem">Certified</th>
                            <th style="padding:.5rem">Completion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['by_class'] as $row)
                            <tr style="border-top: 1px solid rgba(0,0,0,.08)">
                                <td style="padding:.5rem; font-weight:600">{{ $row['class'] }}</td>
                                @if ($schools->isNotEmpty())<td style="padding:.5rem">{{ $row['school'] }}</td>@endif
                                <td style="padding:.5rem">{{ $row['students'] }}</td>
                                <td style="padding:.5rem">{{ $row['experiments_open'] }}</td>
                                <td style="padding:.5rem">{{ $row['certified'] }} ({{ $pct($row['certification_rate']) }})</td>
                                <td style="padding:.5rem">{{ $pct($row['completion_rate']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
