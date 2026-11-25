<?php

namespace App\Actions\Employees;

use App\Models\User;

class ActivateEmployee
{
    public function __invoke(User $employee): User
    {
        $employee->update(['is_active' => true]);

        return $employee;
    }
}
