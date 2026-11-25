<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-canvas text-ink antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6">
            <div class="flex w-full max-w-sm flex-col gap-5">
                <x-app.brand class="justify-center" />

                <div class="rounded-ui border border-border bg-surface p-[22px]">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
