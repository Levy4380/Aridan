<x-layouts::auth :title="__('auth.login.title')">
    <div class="flex flex-col gap-5">
        <x-auth-header :title="__('auth.login.title')" :description="__('auth.login.description')" />

        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-4">
            @csrf

            <flux:input
                name="email"
                :label="__('auth.login.email')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
            />

            <flux:input
                name="password"
                :label="__('auth.login.password')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />

            <flux:checkbox name="remember" :label="__('auth.login.remember')" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('auth.login.submit') }}
            </flux:button>
        </form>

        <p class="text-center text-[13px] text-subtle">{{ __('auth.login.help') }}</p>
    </div>
</x-layouts::auth>
