<?php

/*
| Non-blocking warnings: the system warns, it doesn't block (CLAUDE.md).
| Keys match the warning codes in App\Domain\Warning.
*/

return [
    'capacity_exceeded' => '{1} El :number es para :capacity y son :people: falta 1 lugar. Sumá un colchón extra.|[2,*] El :number es para :capacity y son :people: faltan :count lugares. Sumá colchones extra.',
    'missing_amount' => 'Está :status pero falta el monto o el tipo de algún pago.',
    'missing_total' => 'Está señada pero falta el total de la reserva: no se va a saber cuánto falta cobrar.',
    'deposit_covers_total' => 'Lo cobrado (:paid) ya cubre el total (:total). ¿No está pagada?',
    'similar_guest_name' => 'Ya hay un cliente :name con otro teléfono (:phone). Puede ser la misma persona.',
    'not_movable' => 'Esta reserva no es movible.',
    'color_in_use' => ':name ya usa este color. Se puede, pero en el calendario no se va a distinguir de quién es cada reserva.',
];
