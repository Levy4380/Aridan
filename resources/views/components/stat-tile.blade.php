@props([
    'label',
])

{{-- Data tile (arquitectura-frontend.md, 4.2). --}}
<div {{ $attributes->merge(['class' => 'flex flex-col gap-1 rounded-ui bg-canvas p-3']) }}>
    <span class="text-xs font-semibold text-muted">{{ $label }}</span>
    <span class="text-base font-bold text-ink">{{ $slot }}</span>
</div>
