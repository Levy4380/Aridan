@props([
    'color',
    'size' => 12,
])

{{-- Employee color square (arquitectura-frontend.md, 4.2). --}}
<span
    {{ $attributes->merge(['class' => 'inline-block shrink-0 rounded-[2px] border border-[rgba(20,22,26,0.2)]']) }}
    style="width: {{ $size }}px; height: {{ $size }}px; background-color: {{ $color }};"
    aria-hidden="true"
></span>
