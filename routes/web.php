<?php

use App\Livewire\Apartments\Index as ApartmentsIndex;
use App\Livewire\Availability\Search as AvailabilitySearch;
use App\Livewire\Calendar\Index as CalendarIndex;
use App\Livewire\Employees\Index as EmployeesIndex;
use App\Livewire\Guests\Index as GuestsIndex;
use App\Models\Reservation;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/calendario')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::livewire('calendario', CalendarIndex::class)->name('calendar');
    Route::livewire('buscador', AvailabilitySearch::class)->name('availability');

    // A direct link to a reservation: the calendar on its month, panel open.
    Route::get('reservas/{reservation}', fn (Reservation $reservation) => redirect()->route('calendar', [
        'desde' => $reservation->check_in_date->startOfMonth()->toDateString(),
        'reserva' => $reservation->id,
    ]))->withTrashed()->name('reservations.show');

    Route::livewire('clientes', GuestsIndex::class)->name('guests.index');
    Route::livewire('departamentos', ApartmentsIndex::class)->name('apartments.index');
    Route::livewire('empleados', EmployeesIndex::class)->name('employees.index');
});

require __DIR__.'/settings.php';
