<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configurePasswordConfirmation();
    }

    /**
     * Inactive employees cannot log in. A wrong password still gets Fortify's
     * generic message, so the inactive notice only shows to the employee.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('email', Str::lower($request->string(Fortify::username())))->first();

            if (! $user || ! Hash::check($request->string('password'), $user->password)) {
                return null;
            }

            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    Fortify::username() => __('auth.inactive'),
                ]);
            }

            return $user;
        });
    }

    /**
     * Require a recent password confirmation for Livewire requests from pages that require one.
     */
    private function configurePasswordConfirmation(): void
    {
        Livewire::addPersistentMiddleware([
            RequirePassword::class,
        ]);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
