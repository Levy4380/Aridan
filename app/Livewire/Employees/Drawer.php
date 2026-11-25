<?php

namespace App\Livewire\Employees;

use App\Actions\Employees\ActivateEmployee;
use App\Actions\Employees\CreateEmployee;
use App\Actions\Employees\DeactivateEmployee;
use App\Actions\Employees\UpdateEmployee;
use App\Livewire\Forms\EmployeeForm;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Employee drawer (create / edit, color, activation). Mounted once in the
 * app layout: it opens over any screen with the `open-employee` event.
 * Employees are never deleted, only deactivated.
 */
class Drawer extends Component
{
    public EmployeeForm $form;

    public bool $show = false;

    /**
     * Every employee: for the initials on the swatches and the first free color.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function employees(): Collection
    {
        return User::query()->orderByDesc('is_active')->orderBy('name')->get();
    }

    /**
     * Other employees by color, for the initials on the swatches and the
     * `color_in_use` warning (a repeated color is allowed, but warned about).
     *
     * @return array<string, list<User>>
     */
    #[Computed]
    public function takenColors(): array
    {
        return $this->employees
            ->reject(fn (User $user) => $this->form->employee?->is($user))
            ->groupBy(fn (User $user) => Str::lower($user->color))
            ->map(fn ($users) => $users->values()->all())
            ->all();
    }

    /**
     * @return list<User>
     */
    #[Computed]
    public function colorInUseBy(): array
    {
        return $this->takenColors[Str::lower($this->form->color)] ?? [];
    }

    public function isEditingSelf(): bool
    {
        return $this->form->employee?->is(auth()->user()) ?? false;
    }

    #[On('open-employee')]
    public function open(?int $id = null): void
    {
        $this->resetValidation();
        unset($this->employees, $this->takenColors, $this->colorInUseBy);

        if ($id === null) {
            $this->form->startNew($this->firstFreeColor());
        } else {
            $this->form->fillFrom(User::findOrFail($id));
        }

        $this->show = true;
    }

    public function pickColor(string $color): void
    {
        $this->form->color = Str::lower($color);
    }

    public function save(
        CreateEmployee $createEmployee,
        UpdateEmployee $updateEmployee,
        ActivateEmployee $activateEmployee,
        DeactivateEmployee $deactivateEmployee,
    ): void {
        $this->form->normalize();
        $this->form->validate();

        $employee = $this->form->employee;

        DB::transaction(function () use ($employee, $createEmployee, $updateEmployee, $activateEmployee, $deactivateEmployee) {
            if ($employee === null) {
                $createEmployee([
                    'name' => $this->form->name,
                    'email' => $this->form->email,
                    'password' => $this->form->password,
                    'password_confirmation' => $this->form->password,
                    'color' => $this->form->color,
                ]);

                return;
            }

            $updateEmployee($employee, [
                'name' => $this->form->name,
                'email' => $this->form->email,
                'color' => $this->form->color,
            ]);

            if ($employee->is_active !== $this->form->is_active) {
                $this->form->is_active
                    ? $activateEmployee($employee)
                    : $deactivateEmployee($employee, auth()->user());
            }
        });

        $this->show = false;
        unset($this->employees, $this->takenColors);
        $this->dispatch('employee-changed');

        Flux::toast(variant: 'success', text: $employee === null
            ? __('employees.created', ['name' => $this->form->name])
            : __('employees.updated', ['name' => $this->form->name]));
    }

    public function render(): View
    {
        return view('livewire.employees.drawer', [
            'palette' => config('employee_colors'),
        ]);
    }

    private function firstFreeColor(): string
    {
        $taken = $this->employees->pluck('color')->map(fn ($color) => Str::lower($color))->all();

        foreach (array_keys(config('employee_colors')) as $color) {
            if (! in_array($color, $taken, true)) {
                return $color;
            }
        }

        return array_key_first(config('employee_colors'));
    }
}
