@props([
    'employees',
])

{{-- Calendar legend: employee colors, then the block icons. --}}
<div {{ $attributes->class(['flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs font-semibold text-muted']) }}>
    <span>{{ __('calendar.legend_responsible') }}</span>
    @foreach ($employees as $employee)
        <span class="flex items-center gap-1.5 font-medium text-ink-2">
            <span class="size-3 rounded-[2px] border border-[rgba(20,22,26,0.2)]" style="background-color: {{ $employee->color }}"></span>
            {{ $employee->name }}
        </span>
    @endforeach
    <span class="h-4 w-px bg-divider max-sm:basis-full max-sm:h-0" aria-hidden="true"></span>
    <span class="flex items-center gap-1 font-medium"><flux:icon.lock-closed variant="micro" class="size-3.5" /> {{ __('calendar.legend_fixed') }}</span>
    <span class="flex items-center gap-1 font-medium"><x-icon.sunrise class="size-3.5" /> {{ __('calendar.legend_early') }}</span>
    <span class="flex items-center gap-1 font-medium"><flux:icon.moon variant="micro" class="size-3.5" /> {{ __('calendar.legend_late') }}</span>
    <span class="flex items-center gap-1 font-medium"><x-icon.parking class="size-3.5" /> {{ __('calendar.legend_parking') }}</span>
    <span class="flex items-center gap-1 font-medium"><x-icon.breakfast class="size-3.5" /> {{ __('calendar.legend_breakfast') }}</span>
</div>
