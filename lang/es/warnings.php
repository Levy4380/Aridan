<?php

/*
| Non-blocking warnings: the system warns, it doesn't block (CLAUDE.md).
| Keys match the warning codes in App\Domain\Warning.
*/

return [
    'capacity_exceeded' => '{1} El :number es para :capacity y son :people: falta 1 lugar. Sumá un colchón extra.|[2,*] El :number es para :capacity y son :people: faltan :count lugares. Sumá colchones extra.',
    'missing_amount' => 'Está :status pero falta el monto o el tipo de pago.',
    'similar_guest_name' => 'Ya hay un cliente :name con otro teléfono (:phone). Puede ser la misma persona.',
    'not_movable' => 'Esta reserva no es movible.',
    'color_in_use' => ':name ya usa este color. Se puede, pero en el calendario no se va a distinguir de quién es cada reserva.',
];
