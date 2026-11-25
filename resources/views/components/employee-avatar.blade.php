@props([
    'user',
    'size' => 32,
])

{{-- Round avatar painted with the employee's color, so everyone keeps seeing which color is theirs. --}}
<span
    {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-full border border-[rgba(20,22,26,0.2)] font-bold']) }}
    style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.42) }}px; background-color: {{ $user->color }}; color: {{ \App\Support\ColorContrast::textOn($user->color) }};"
    aria-hidden="true"
>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}</span>
