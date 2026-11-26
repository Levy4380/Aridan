<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' · '.config('app.name') : config('app.name') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

{{-- Installable as an app on phones ("Agregar a inicio"), mainly iOS. No offline support yet. --}}
<link rel="manifest" href="/manifest.webmanifest">
<meta name="application-name" content="{{ config('app.name') }}">
<meta name="theme-color" content="#ffffff">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
<meta name="apple-mobile-web-app-status-bar-style" content="default">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
{{-- No @fluxAppearance: the app stays in light mode (arquitectura-frontend.md, 3.5). --}}
