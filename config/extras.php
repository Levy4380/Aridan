<?php

/*
|--------------------------------------------------------------------------
| Extras
|--------------------------------------------------------------------------
|
| `enabled`: the whole extras flow (form toggles, detail, calendar icons).
| Off for now: reservations don't include parking or breakfast. The code,
| tables and catalog stay, so turning it on brings everything back; while
| off, saving a reservation never touches the extras it already has.
| `seed`: the catalog loaded by ExtraSeeder (first iteration).
| `icons`: extra name => Blade icon component (<x-icon.{name}>). An extra
| without an icon is shown by its name only.
|
*/

return [
    'enabled' => (bool) env('EXTRAS_ENABLED', false),

    'seed' => ['Cochera', 'Desayuno'],

    'icons' => [
        'Cochera' => 'parking',
        'Desayuno' => 'breakfast',
    ],
];
