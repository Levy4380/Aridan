@props([
    'apartment',
])

<div class="sticky left-0 z-20 flex min-w-0 flex-col items-start justify-center gap-1 border-r border-divider bg-surface px-2 sm:gap-2 sm:px-3" style="grid-row: 1; grid-column: 1;">
    <span @class([
        'text-[15px] font-bold',
        'text-ink' => $apartment->is_active,
        'text-disabled' => ! $apartment->is_active,
    ])>{{ $apartment->number }}</span>
    <span class="text-xs whitespace-nowrap text-subtle">{{ \App\Support\Format::people($apartment->capacity) }}</span>
    @unless ($apartment->is_active)
        <x-status-badge :active="false" />
    @endunless
</div>
