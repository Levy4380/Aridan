{{-- Breakfast extra: steaming cup. --}}
<svg {{ $attributes->except('title')->merge(['class' => 'size-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" @if ($attributes->has('title')) role="img" aria-label="{{ $attributes->get('title') }}" @else aria-hidden="true" @endif>
    @if ($attributes->has('title'))<title>{{ $attributes->get('title') }}</title>@endif
    <path d="M17 8h1a4 4 0 1 1 0 8h-1" />
    <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z" />
    <path d="M7 2v2" />
    <path d="M11 2v2" />
</svg>
