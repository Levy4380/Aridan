@props([
    'palette',      // [hex => name]
    'selected',     // current hex
    'takenBy' => [], // [hex => list<User>] other employees using each color
    'pick',         // Livewire method that receives the hex
])

{{-- Employee color picker (arquitectura-frontend.md, 4.2). --}}
<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }} role="group">
    @foreach ($palette as $hex => $name)
        @php
            $isSelected = strtolower($selected) === $hex;
            $owners = $takenBy[$hex] ?? [];
        @endphp
        <button
            type="button"
            wire:click="{{ $pick }}('{{ $hex }}')"
            aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
            aria-label="{{ $name }}{{ $owners ? ' · '.__('employees.swatch_taken_by', ['names' => collect($owners)->pluck('name')->join(', ')]) : '' }}"
            title="{{ $name }}"
            @class([
                'relative flex size-11 items-center justify-center rounded-[2px] border border-[rgba(20,22,26,0.2)] text-sm font-bold',
                'ring-2 ring-white ring-offset-2 ring-offset-accent' => $isSelected,
            ])
            style="background-color: {{ $hex }}; color: {{ \App\Support\ColorContrast::textOn($hex) }};"
        >
            @if ($isSelected)
                <flux:icon.check class="size-5" variant="mini" />
            @elseif ($owners)
                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($owners[0]->name, 0, 1)) }}
            @endif
        </button>
    @endforeach
</div>
