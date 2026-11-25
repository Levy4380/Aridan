<?php

namespace App\Actions\Employees;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Edits an employee's name, email and color. Any employee can edit any other
 * (there are no roles). Activation has its own actions.
 */
class UpdateEmployee
{
    use ProfileValidationRules;

    /**
     * @param  array{name: string, email: string, color: string}  $input
     */
    public function __invoke(User $employee, array $input): User
    {
        $input['email'] = Str::lower(trim($input['email'] ?? ''));
        $input['color'] = Str::lower(trim($input['color'] ?? ''));

        $validated = Validator::make($input, [
            ...$this->profileRules($employee->id),
            'color' => $this->colorRules(),
        ])->validate();

        $employee->update($validated);

        return $employee;
    }
}
