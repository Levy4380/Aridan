<?php

/*
|--------------------------------------------------------------------------
| Extras
|--------------------------------------------------------------------------
|
| `seed`: the catalog loaded by ExtraSeeder (first iteration).
| `icons`: extra name => Blade icon component (<x-icon.{name}>). An extra
| without an icon is shown by its name only.
|
*/

return [
    'seed' => ['Cochera', 'Desayuno'],

    'icons' => [
        'Cochera' => 'parking',
        'Desayuno' => 'breakfast',
    ],
];
