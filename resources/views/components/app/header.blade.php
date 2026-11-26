@php
    // Main navigation (arquitectura-frontend.md, 2). Each screen shows up in
    // the nav as soon as its route exists, following the construction order.
    $items = collect([
        ['route' => 'calendar', 'active' => 'calendar', 'label' => __('nav.calendar')],
        ['route' => 'availability', 'active' => 'availability', 'label' => __('nav.availability')],
        ['route' => 'guests.index', 'active' => 'guests.*', 'label' => __('nav.guests')],
        ['route' => 'billing.pending', 'active' => 'billing.*', 'label' => __('nav.billing')],
        ['route' => 'apartments.index', 'active' => 'apartments.*', 'label' => __('nav.apartments')],
        ['route' => 'employees.index', 'active' => 'employees.*', 'label' => __('nav.employees')],
    ])->filter(fn (array $item) => Route::has($item['route']));

    $user = auth()->user();
@endphp

{{--
    Wide screens: the nav sits in the header. Narrow screens (< lg): it
    folds into a menu button that opens the same links as a full-width list,
    so the header stays one line and the page starts right away.
--}}
<header
    x-data="{ menu: false }"
    x-on:keydown.escape.window="menu = false"
    {{ $attributes->class(['relative z-30 flex min-h-14 items-center gap-x-2 lg:gap-x-6 border-b border-border bg-surface px-4 py-2 sm:px-6 lg:min-h-[60px] lg:py-2.5']) }}
>
    <button
        type="button"
        class="-ms-2 flex size-10 items-center justify-center rounded-ui text-ink-2 hover:bg-canvas lg:hidden"
        x-on:click="menu = ! menu"
        x-bind:aria-expanded="menu"
        aria-controls="mobile-nav"
        aria-label="{{ __('nav.menu') }}"
        data-test="mobile-nav-button"
    >
        <flux:icon.bars-3 x-show="! menu" class="size-6" />
        <flux:icon.x-mark x-show="menu" x-cloak class="size-6" />
    </button>

    <x-app.brand :href="route('calendar')" wire:navigate />

    <nav aria-label="{{ __('nav.main') }}" class="flex flex-wrap items-center gap-1 max-lg:hidden">
        @foreach ($items as $item)
            @php($current = request()->routeIs($item['active']))
            <a
                href="{{ route($item['route']) }}"
                wire:navigate
                @if ($current) aria-current="page" @endif
                @class([
                    'rounded-ui px-3 py-2 text-sm transition-colors',
                    'bg-accent-soft font-semibold text-accent' => $current,
                    'font-medium text-muted hover:bg-canvas hover:text-ink' => ! $current,
                ])
            >{{ $item['label'] }}</a>
        @endforeach
    </nav>

    {{-- Narrow screens: the same links, one per row, big enough to tap. --}}
    <nav
        id="mobile-nav"
        aria-label="{{ __('nav.main') }}"
        x-show="menu"
        x-cloak
        x-transition.opacity.duration.150ms
        x-on:click.outside="menu = false"
        class="absolute inset-x-0 top-full flex flex-col gap-0.5 border-b border-border bg-surface p-2 shadow-lg lg:hidden"
    >
        @foreach ($items as $item)
            @php($current = request()->routeIs($item['active']))
            <a
                href="{{ route($item['route']) }}"
                wire:navigate
                @if ($current) aria-current="page" @endif
                @class([
                    'rounded-ui px-3 py-3 text-[15px]',
                    'bg-accent-soft font-semibold text-accent' => $current,
                    'font-medium text-ink-2 active:bg-canvas' => ! $current,
                ])
            >{{ $item['label'] }}</a>
        @endforeach
    </nav>

    <div class="ms-auto">
        <flux:dropdown position="bottom" align="end">
            <button type="button" class="flex items-center gap-2.5 rounded-ui py-1 ps-2 pe-1 text-sm font-medium text-ink-2 hover:bg-canvas" data-test="user-menu-button">
                <span class="whitespace-nowrap max-sm:sr-only">{{ $user->name }}</span>
                <x-employee-avatar :user="$user" />
            </button>

            <flux:menu>
                <div class="px-2 py-1.5 text-sm leading-tight">
                    <div class="font-semibold text-ink">{{ $user->name }}</div>
                    <div class="text-subtle">{{ $user->email }}</div>
                </div>
                <flux:menu.separator />
                <flux:menu.item :href="route('profile.edit')" icon="user" wire:navigate>
                    {{ __('nav.profile') }}
                </flux:menu.item>
                {{-- Hidden once opened as an installed app. Native prompt where there is one; otherwise the steps (iOS). --}}
                <flux:menu.item
                    icon="device-phone-mobile"
                    x-data
                    x-show="! window.aridanInstall?.isInstalled()"
                    x-on:click="window.aridanInstall?.prompt().then((shown) => shown || $flux.modal('install-app').show())"
                    class="cursor-pointer"
                    data-test="install-app-button"
                >
                    {{ __('nav.install.button') }}
                </flux:menu.item>
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('nav.logout') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </div>

    {{-- How to install it, for browsers that can't do it from the page (iOS). A drawer, like everything else. --}}
    <flux:modal name="install-app" variant="flyout" class="w-full max-md:max-w-none max-md:p-5 md:w-[480px]" data-test="install-app-drawer">
        <div class="flex flex-col gap-5" x-data="{ ios: window.aridanInstall?.isIos() ?? false }">
            <div class="flex items-center gap-3 pe-8">
                <img src="/apple-touch-icon.png" alt="" class="size-12 rounded-[11px]" />
                <div>
                    <h2 class="text-[20px] font-bold text-ink">{{ __('nav.install.title', ['app' => config('app.name')]) }}</h2>
                    <p class="text-[13px] text-subtle">{{ __('nav.install.intro') }}</p>
                </div>
            </div>

            <ol class="flex flex-col gap-3 text-sm text-ink-2" x-show="ios">
                <li class="flex items-start gap-3">
                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xs font-bold text-accent">1</span>
                    <span>{!! __('nav.install.ios_share') !!} <flux:icon.arrow-up-on-square variant="mini" class="inline size-4 align-text-bottom text-accent" /> {{ __('nav.install.ios_share_where') }}</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xs font-bold text-accent">2</span>
                    <span>{!! __('nav.install.ios_add') !!}</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xs font-bold text-accent">3</span>
                    <span>{!! __('nav.install.ios_confirm') !!}</span>
                </li>
            </ol>

            <ol class="flex flex-col gap-3 text-sm text-ink-2" x-show="! ios" x-cloak>
                <li class="flex items-start gap-3">
                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xs font-bold text-accent">1</span>
                    <span>{!! __('nav.install.other_menu') !!}</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xs font-bold text-accent">2</span>
                    <span>{!! __('nav.install.other_add') !!}</span>
                </li>
            </ol>

            <p class="rounded-ui bg-canvas p-3 text-[13px] text-subtle">{{ __('nav.install.after') }}</p>

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="primary">{{ __('nav.install.done') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</header>
