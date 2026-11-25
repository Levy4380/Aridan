<?php

namespace App\Livewire\Employees;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Employees screen (/empleados): the list. The employee form is the
 * Employees\Drawer, mounted in the layout and opened with `open-employee`.
 */
class Index extends Component
{
    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function employees(): Collection
    {
        return User::query()->orderByDesc('is_active')->orderBy('name')->get();
    }

    #[On('employee-changed')]
    public function refresh(): void
    {
        unset($this->employees);
    }

    public function render(): View
    {
        return view('livewire.employees.index')->title(__('employees.title'));
    }
}
