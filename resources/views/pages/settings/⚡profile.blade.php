<?php

use App\Concerns\ProfileValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';

    public function rendering($view): void
    {
        $view->title(__('settings.profile.title'));
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $this->email = Str::lower(trim($this->email));

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated)->save();

        Flux::toast(variant: 'success', text: __('settings.profile.updated'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('settings.profile.title')" :subheading="__('settings.profile.description')">
        <form wire:submit="updateProfileInformation" class="mt-6 space-y-5">
            <flux:input wire:model="name" :label="__('settings.profile.name')" type="text" required autofocus autocomplete="name" />

            <flux:input wire:model="email" :label="__('settings.profile.email')" type="email" required autocomplete="email" />

            <flux:button variant="primary" type="submit" data-test="update-profile-button">
                {{ __('settings.save') }}
            </flux:button>
        </form>
    </x-pages::settings.layout>
</section>
