<?php

namespace App\Actions\Employees;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Deactivates an employee: they can no longer log in, and their reservations
 * keep their color. Employees are never deleted.
 *
 * An employee cannot deactivate themselves, so the app can never end up
 * without anyone able to log in and reactivate the rest.
 */
class DeactivateEmployee
{
    public function __invoke(User $employee, User $actor): User
    {
        if ($employee->is($actor)) {
            throw ValidationException::withMessages([
                'is_active' => __('employees.cannot_deactivate_self'),
            ]);
        }

        $employee->update(['is_active' => false]);

        return $employee;
    }
}
