@props([
    'days',  // list<App\Queries\Availability\OccupancyDay>
    'label',
])

@php
    use App\Queries\Availability\OccupancyDay;

    $color = fn (string $status) => match ($status) {
        OccupancyDay::FIXED => 'bg-occ-fixed',
        OccupancyDay::MOVABLE => 'bg-occ-movable',
        default => 'bg-occ-free',
    };
@endphp

{{-- One 26px square per day, split in morning / afternoon (arquitectura-frontend.md, 4.2). --}}
<div {{ $attributes->merge(['class' => 'flex gap-0.5']) }} role="img" aria-label="{{ $label }}">
    @foreach ($days as $day)
        <div
            @class([
                'flex h-[26px] w-[26px] shrink-0 gap-px overflow-hidden rounded-[2px]',
                'outline-2 -outline-offset-2 outline-occ-requested-border' => $day->isRequested(),
            ])
            title="{{ $day->title }}"
        >
            <span @class(['h-full flex-1', $color($day->morning), 'bg-occ-requested!' => $day->morningRequested && $day->morning === OccupancyDay::FREE])></span>
            <span @class(['h-full flex-1', $color($day->afternoon), 'bg-occ-requested!' => $day->afternoonRequested && $day->afternoon === OccupancyDay::FREE])></span>
        </div>
    @endforeach
</div>
