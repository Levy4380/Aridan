@props([
    'fit', // App\Domain\CapacityFit
])

@php
    use App\Domain\CapacityFit;

    [$classes, $text] = match ($fit->kind) {
        CapacityFit::EXACT => ['bg-success-bg text-success-fg', __('availability.fit.exact')],
        CapacityFit::BIGGER => ['bg-accent-soft text-accent', trans_choice('availability.fit.bigger', $fit->difference, ['count' => $fit->difference])],
        CapacityFit::SMALLER => ['bg-warning-bg text-warning-badge', trans_choice('availability.fit.smaller', $fit->difference, ['count' => $fit->difference])],
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center whitespace-nowrap rounded-[3px] px-2 py-0.5 text-xs font-bold', $classes]) }}>{{ $text }}</span>
