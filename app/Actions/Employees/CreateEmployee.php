<?php

namespace App\Actions\Employees;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Creates an employee. There is no public registration: the first employee
 * comes from the `employee:create` command, the rest from the Employees screen.
 */
class CreateEmployee
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * @param  array{name: string, email: string, password: string, password_confirmation: string, color: string}  $input
     */
    public function __invoke(array $input): User
    {
        $input['email'] = Str::lower(trim($input['email'] ?? ''));
        $input['color'] = Str::lower(trim($input['color'] ?? ''));

        $validated = Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'color' => $this->colorRules(),
        ])->validate();

        return User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'color' => $validated['color'],
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
