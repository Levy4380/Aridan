<x-layouts::auth :title="__('auth.confirm.title')">
    <div class="flex flex-col gap-5">
        <x-auth-header :title="__('auth.confirm.title')" :description="__('auth.confirm.description')" />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-4">
            @csrf

            <flux:input
                name="password"
                :label="__('auth.login.password')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                {{ __('auth.confirm.submit') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
