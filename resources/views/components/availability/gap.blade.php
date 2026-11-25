@props([
    'availability', // App\Queries\Availability\ApartmentAvailability
])

@php
    $before = $availability->freeDaysBefore();
    $after = $availability->freeDaysAfter();

    $side = fn (int $days, string $key) => match (true) {
        $days === 0 => __("availability.gap.{$key}_none"),
        $days > 14 => __("availability.gap.{$key}_long"),
        default => trans_choice("availability.gap.{$key}_days", $days, ['count' => $days]),
    };
@endphp

<span {{ $attributes }}>
    @if ($before === 0 && $after === 0)
        {{ __('availability.gap.exact') }}
    @else
        {{ $side($before, 'before') }} · {{ $side($after, 'after') }}
    @endif
</span>
