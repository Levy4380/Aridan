<?php

namespace App\Livewire\Forms;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Form;

class EmployeeForm extends Form
{
    use PasswordValidationRules, ProfileValidationRules;

    public ?User $employee = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $color = '';

    public bool $is_active = true;

    public function fillFrom(User $employee): void
    {
        $this->employee = $employee;
        $this->name = $employee->name;
        $this->email = $employee->email;
        $this->password = '';
        $this->color = $employee->color;
        $this->is_active = $employee->is_active;
    }

    public function startNew(string $color): void
    {
        $this->reset();
        $this->color = $color;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            ...$this->profileRules($this->employee?->id),
            'color' => $this->colorRules(),
            'is_active' => ['boolean'],
            ...($this->employee === null
                // Only one "initial password" field in the modal: no confirmation.
                ? ['password' => array_values(array_filter($this->passwordRules(), fn ($rule) => $rule !== 'confirmed'))]
                : []),
        ];
    }

    /**
     * Same normalization the actions apply, so the unique check matches.
     */
    public function normalize(): void
    {
        $this->email = Str::lower(trim($this->email));
        $this->color = Str::lower(trim($this->color));
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => __('employees.fields.name'),
            'email' => __('employees.fields.email'),
            'password' => __('employees.fields.password'),
            'color' => __('employees.fields.color'),
        ];
    }
}
