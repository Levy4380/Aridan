@props([
    'title',
    'confirm' => null,   // Livewire method for "Sí, eliminar"; null when deleting is blocked
    'confirmLabel',
    'backLabel',
])

{{-- Inline confirmation for a permanent delete, inside a drawer (no modals). Same look as cancelling a reservation. --}}
<div role="alertdialog" aria-labelledby="delete-title-{{ $this->getId() }}" {{ $attributes->merge(['class' => 'flex flex-col gap-3 rounded-ui border border-danger-border bg-danger-bg p-4']) }}>
    <h3 id="delete-title-{{ $this->getId() }}" class="text-[17px] font-bold text-danger-fg">{{ $title }}</h3>
    <div class="flex flex-col gap-3 text-sm text-danger-fg">{{ $slot }}</div>
    <div class="flex flex-wrap justify-end gap-2">
        <flux:button variant="filled" wire:click="cancelDelete">{{ $backLabel }}</flux:button>
        @if ($confirm)
            <flux:button variant="danger" wire:click="{{ $confirm }}" data-test="confirm-delete-button">{{ $confirmLabel }}</flux:button>
        @endif
    </div>
</div>
