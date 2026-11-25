{{-- Parking extra: "P" in a rounded square. --}}
<svg {{ $attributes->except('title')->merge(['class' => 'size-4']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" @if ($attributes->has('title')) role="img" aria-label="{{ $attributes->get('title') }}" @else aria-hidden="true" @endif>
    @if ($attributes->has('title'))<title>{{ $attributes->get('title') }}</title>@endif
    <rect x="3" y="3" width="18" height="18" rx="4" />
    <path d="M9 17V7h4a3 3 0 0 1 0 6H9" />
</svg>
