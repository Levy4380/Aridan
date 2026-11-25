@props([
    'status',
])

@if ($status)
    <div role="status" {{ $attributes->merge(['class' => 'rounded-ui border border-success-border bg-success-bg px-3 py-2 text-sm font-medium text-success-fg']) }}>
        {{ $status }}
    </div>
@endif
