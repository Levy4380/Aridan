<div class="flex items-start gap-10 max-md:flex-col max-md:gap-4">
    <nav aria-label="{{ __('settings.title') }}" class="flex w-full flex-col gap-1 md:w-[220px]">
        @foreach ([
            ['route' => 'profile.edit', 'label' => __('settings.profile.title')],
            ['route' => 'security.edit', 'label' => __('settings.password.title')],
        ] as $item)
            @php($current = request()->routeIs($item['route']))
            <a
                href="{{ route($item['route']) }}"
                wire:navigate
                @if ($current) aria-current="page" @endif
                @class([
                    'rounded-ui px-3 py-2 text-sm transition-colors',
                    'bg-accent-soft font-semibold text-accent' => $current,
                    'font-medium text-muted hover:bg-surface hover:text-ink' => ! $current,
                ])
            >{{ $item['label'] }}</a>
        @endforeach
    </nav>

    <div class="w-full max-w-lg flex-1 rounded-ui border border-border bg-surface p-[18px]">
        <h2 class="text-[17px] font-bold text-ink">{{ $heading ?? '' }}</h2>
        <p class="mt-1 text-sm text-subtle">{{ $subheading ?? '' }}</p>

        {{ $slot }}
    </div>
</div>
