<x-filament-panels::page>
    <form wire:submit="import" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray" wire:loading.attr="disabled">
            Import students
        </x-filament::button>
    </form>

    @if ($created !== null)
        <x-filament::section>
            <x-slot name="heading">Done</x-slot>
            {{ $created }} student accounts were created. Each one was sent a welcome email.
        </x-filament::section>
    @endif

    @if ($rowErrors !== [])
        <x-filament::section>
            <x-slot name="heading">Nothing was imported. These rows need fixing:</x-slot>

            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left">
                        <th class="py-2 pr-4">Line</th>
                        <th class="py-2">Problem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rowErrors as $error)
                        <tr class="border-t border-gray-200 dark:border-white/10 align-top">
                            <td class="py-2 pr-4 font-semibold">{{ $error['line'] }}</td>
                            <td class="py-2">
                                @foreach ($error['messages'] as $message)
                                    <div>{{ $message }}</div>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-filament::section>
    @endif

    <x-filament::section collapsible collapsed>
        <x-slot name="heading">What the file looks like</x-slot>
        <pre class="text-sm">name,email,class
Ayesha Khan,ayesha.khan@school.edu.pk,Class 9-A
Bilal Ahmed,bilal.ahmed@school.edu.pk,Class 9-A</pre>
    </x-filament::section>
</x-filament-panels::page>
