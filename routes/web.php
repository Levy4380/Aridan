<?php

use App\Livewire\Apartments\Index as ApartmentsIndex;
use App\Livewire\Availability\Search as AvailabilitySearch;
use App\Livewire\Calendar\Index as CalendarIndex;
use App\Livewire\Employees\Index as EmployeesIndex;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/calendario')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::livewire('calendario', CalendarIndex::class)->name('calendar');
    Route::livewire('buscador', AvailabilitySearch::class)->name('availability');

    Route::livewire('departamentos', ApartmentsIndex::class)->name('apartments.index');
    Route::livewire('empleados', EmployeesIndex::class)->name('employees.index');
});

require __DIR__.'/settings.php';
