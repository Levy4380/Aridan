@php
    // Main navigation (arquitectura-frontend.md, 2). Each screen shows up in
    // the nav as soon as its route exists, following the construction order.
    $items = collect([
        ['route' => 'calendar', 'active' => 'calendar', 'label' => __('nav.calendar')],
        ['route' => 'availability', 'active' => 'availability', 'label' => __('nav.availability')],
        ['route' => 'guests.index', 'active' => 'guests.*', 'label' => __('nav.guests')],
        ['route' => 'apartments.index', 'active' => 'apartments.*', 'label' => __('nav.apartments')],
        ['route' => 'employees.index', 'active' => 'employees.*', 'label' => __('nav.employees')],
    ])->filter(fn (array $item) => Route::has($item['route']));

    $user = auth()->user();
@endphp

<header class="flex min-h-[60px] flex-wrap items-center gap-x-6 gap-y-2 border-b border-border bg-surface px-6 py-2.5">
    <x-app.brand :href="route('calendar')" wire:navigate />

    <nav aria-label="{{ __('nav.main') }}" class="flex flex-wrap items-center gap-1 max-sm:order-last max-sm:w-full">
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

    <div class="ms-auto">
        <flux:dropdown position="bottom" align="end">
            <button type="button" class="flex items-center gap-2.5 rounded-ui py-1 ps-2 pe-1 text-sm font-medium text-ink-2 hover:bg-canvas" data-test="user-menu-button">
                <span class="max-sm:sr-only">{{ $user->name }}</span>
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
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('nav.logout') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </div>
</header>
