<?php

return [
    'title' => 'Buscador',
    'description' => 'Toda reserva nueva arranca acá: elegí fechas y personas, y el sistema sugiere el mejor departamento.',
    'check_in' => 'Entrada',
    'check_out' => 'Salida',
    'people' => 'Personas',
    'early_check_in' => 'Entrada temprana',
    'late_check_out' => 'Salida tardía',
    'nights' => '{1} 1 noche|[2,*] :count noches',
    'summary_range' => 'del :from al :to',
    'invalid_dates' => 'La salida tiene que ser posterior a la entrada.',
    'for_guest' => 'Nueva reserva para :name: elegí fechas y departamento.',
    'for_guest_clear' => 'Quitar',
    'people_prompt' => 'Ingresá cuántas personas son (1 o más) para ver los departamentos.',

    'available' => 'Disponibles (:count)',
    'none_available' => 'No hay departamentos libres para esas fechas. Revisá los ocupados por reservas movibles, más abajo.',
    'best' => 'Mejor opción',
    'reserve' => 'Reservar',

    'movable' => 'Ocupados por reservas movibles (:count)',
    'movable_help' => 'Se podrían liberar moviendo esas reservas a otro departamento.',
    'occupied_by' => 'Ocupado por :guest (:from al :to)',

    'unavailable' => 'No disponibles (:count)',
    'reason_occupied' => 'ocupado',
    'reason_inactive' => 'inactivo',

    'columns' => [
        'apartment' => 'Depto',
        'capacity' => 'Capacidad',
        'gap' => 'Días sueltos',
    ],

    'fit' => [
        'exact' => 'Capacidad justa',
        'bigger' => '{1} Sobra 1 lugar|[2,*] Sobran :count',
        'smaller' => '{1} Falta 1 · colchón|[2,*] Faltan :count · colchones',
    ],

    'gap' => [
        'exact' => 'Encaja exacto en el hueco',
        'before_none' => 'Justo antes',
        'before_days' => '{1} 1 día antes|[2,*] :count días antes',
        'before_long' => 'más de 2 semanas antes',
        'after_none' => 'justo después',
        'after_days' => '{1} 1 día después|[2,*] :count días después',
        'after_long' => 'más de 2 semanas después',
    ],

    'legend' => [
        'fixed' => 'Reserva fija',
        'movable' => 'Reserva movible',
        'free' => 'Libre',
        'requested' => 'Lo pedido',
        'help' => 'Cada cuadrado es un día: mitad izquierda mañana, derecha tarde.',
    ],

    'map' => [
        'label' => 'Ocupación del depto :number alrededor de las fechas pedidas',
        'free' => 'libre',
        'fixed' => 'fija',
        'movable' => 'movible',
    ],

    'edit' => [
        'title' => 'Cambiar reserva',
        'description' => 'Elegí las nuevas fechas o personas y el departamento. La reserva no choca consigo misma.',
        'banner' => 'Cambiando la reserva de :guest (depto :number, del :from al :to).',
        'back' => 'Volver a la reserva',
        'current' => 'Actual',
        'use' => 'Usar este',
        'confirm' => '¿Pasar la reserva de :guest al depto :number, del :from al :to, para :people?',
        'apply' => 'Confirmar cambio',
        'cancel' => 'Volver',
        'saved' => 'Reserva de :guest actualizada.',
    ],
];
