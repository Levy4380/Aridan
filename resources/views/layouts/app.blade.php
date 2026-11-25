<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-canvas text-ink antialiased">
        <x-app.header />

        <main {{ $attributes->merge(['class' => 'mx-auto flex w-full max-w-[1440px] flex-col gap-4 p-6']) }}>
            {{ $slot }}
        </main>

        {{--
            Drawers live here, not in the pages: they open over any screen
            with an event (open-reservation, open-guest, open-apartment,
            open-employee) and closing one leaves you where you were. They
            stack: a guest file opened from a reservation closes back to it.
        --}}
        <livewire:reservations.panel />
        <livewire:guests.drawer />
        <livewire:apartments.drawer />
        <livewire:employees.drawer />

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
