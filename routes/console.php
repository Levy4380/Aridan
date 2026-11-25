<?php

use Illuminate\Support\Facades\Schedule;

// Holidays are reference data for the calendar: refresh this year and the
// next once a month (Laravel Cloud runs the scheduler).
Schedule::command('holidays:sync')->monthly()->withoutOverlapping();
