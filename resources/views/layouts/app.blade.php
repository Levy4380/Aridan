<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-canvas text-ink antialiased">
        <x-app.header class="print:hidden" />

        {{-- The calendar uses the whole width; the other screens keep a readable max. --}}
        <main {{ $attributes->class([
            'mx-auto flex w-full min-w-0 flex-col gap-4 p-4 sm:p-6 print:p-0',
            'max-w-[1440px]' => ! request()->routeIs('calendar'),
        ]) }}>
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
        <livewire:billing.invoice-drawer />

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
