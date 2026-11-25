@props([
    'status', // App\Domain\PaymentStatus
])

{{-- Reservado / Señado / Pagado. --}}
<span {{ $attributes->class([
    'inline-flex items-center whitespace-nowrap rounded-[3px] px-2 py-0.5 text-xs font-bold',
    'bg-success-bg text-success-fg' => $status === \App\Domain\PaymentStatus::Paid,
    'bg-accent-soft text-accent' => $status === \App\Domain\PaymentStatus::Deposit,
    'bg-chip text-subtle' => $status === \App\Domain\PaymentStatus::Pending,
]) }}>{{ $status->label() }}</span>
