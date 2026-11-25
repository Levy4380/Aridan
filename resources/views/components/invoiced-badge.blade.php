@props([
    'invoiced',
])

{{-- Facturada / No facturada. --}}
<span {{ $attributes->class([
    'inline-flex items-center whitespace-nowrap rounded-[3px] px-2 py-0.5 text-xs font-bold',
    'bg-success-bg text-success-fg' => $invoiced,
    'bg-warning-bg text-warning-badge' => ! $invoiced,
]) }}>{{ $invoiced ? __('reservations.invoicing.invoiced') : __('reservations.invoicing.not_invoiced') }}</span>
