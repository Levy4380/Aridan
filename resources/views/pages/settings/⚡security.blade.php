<?php

use App\Concerns\PasswordValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function rendering($view): void
    {
        $view->title(__('settings.password.title'));
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('settings.password.updated'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('settings.password.title')" :subheading="__('settings.password.description')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-5">
            <flux:input
                wire:model="current_password"
                :label="__('settings.password.current')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />
            <flux:input
                wire:model="password"
                :label="__('settings.password.new')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />
            <flux:input
                wire:model="password_confirmation"
                :label="__('settings.password.confirm')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <flux:button variant="primary" type="submit" data-test="update-password-button">
                {{ __('settings.save') }}
            </flux:button>
        </form>
    </x-pages::settings.layout>
</section>
