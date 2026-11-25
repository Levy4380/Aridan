@props([
    'title',
    'description' => null,
])

{{-- Page header pattern (arquitectura-frontend.md, 2): h1, help line and the main action on the right. --}}
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-start justify-between gap-4']) }}>
    <div>
        <h1 class="text-[26px] font-bold leading-tight tracking-[-0.01em] text-ink">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-subtle">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
