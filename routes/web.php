<?php

use App\Livewire\Apartments\Index as ApartmentsIndex;
use App\Livewire\Employees\Index as EmployeesIndex;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/calendario')->name('home');

Route::middleware(['auth'])->group(function () {
    // Placeholder until the calendar grid is built (construction step 3).
    Route::view('calendario', 'calendar')->name('calendar');

    Route::livewire('departamentos', ApartmentsIndex::class)->name('apartments.index');
    Route::livewire('empleados', EmployeesIndex::class)->name('employees.index');
});

require __DIR__.'/settings.php';
