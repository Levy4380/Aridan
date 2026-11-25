<?php

return [
    'people_short' => ':count pers.',
    'people_long' => '{1} 1 persona|[2,*] :count personas',
    'created' => 'Reserva de :guest guardada en el depto :number.',

    'timing' => [
        'upcoming' => 'Próxima',
        'current' => 'En curso',
        'past' => 'Pasada',
        'cancelled' => 'Cancelada',
    ],

    'half' => [
        'morning' => 'mañana',
        'afternoon' => 'tarde',
    ],

    'payment_status' => [
        'pending' => 'Reservado',
        'deposit' => 'Señado',
        'paid' => 'Pagado',
    ],

    'payment_method' => [
        'transfer' => 'Transferencia',
        'cash' => 'Efectivo',
    ],

    'fields' => [
        'first_name' => 'Nombre',
        'last_name' => 'Apellido',
        'phone' => 'Teléfono',
        'document' => 'DNI/CUIT',
        'people' => 'personas',
        'payment_method' => 'Tipo de pago',
        'amount' => 'Monto cobrado',
        'movable' => 'Movible',
        'movable_help' => 'Se puede cambiar de depto. Desmarcalo si el huésped pidió este depto o se lo ofreciste en particular (por ejemplo, con fotos).',
        'extra_mattresses' => 'Colchones extra',
        'notes' => 'Nota de la reserva',
        'notes_placeholder' => 'Preferencias, horario de llegada, reserva familiar…',
    ],

    'create' => [
        'title' => 'Nueva reserva',
        'change' => 'Cambiar',
        'apartment' => 'Departamento',
        'guest' => 'Cliente',
        'payment' => 'Pago',
        'no_payment_help' => 'Sin pago todavía: el tipo de pago y el monto se cargan cuando se seña o se paga.',
        'conditions' => 'Condiciones',
        'mattress_less' => 'Un colchón menos',
        'mattress_more' => 'Un colchón más',
        'responsible' => 'Responsable',
        'responsible_help' => 'Quien hizo la reserva. Si la cargás por otro empleado, elegilo acá: la reserva se pinta con su color.',
        'warnings_heading' => 'Revisá antes de guardar',
        'save' => 'Guardar reserva',
        'save_anyway' => 'Guardar igual',
        'saving' => 'Guardando…',
    ],

    'errors' => [
        'overlap' => 'El depto :number ya está ocupado en esas fechas por la reserva de :guest (:from al :to).',
        'overlap_generic' => 'Otra reserva ocupa ese depto en esas fechas. Elegí otro.',
        'apartment_inactive' => 'El depto :number está inactivo: no se puede reservar.',
        'employee_inactive' => 'El responsable tiene que ser un empleado activo.',
        'guest_required' => 'Elegí un cliente o cargá uno nuevo.',
        'phone_taken' => 'Este teléfono ya pertenece a :name.',
        'document_taken' => 'Este DNI/CUIT ya pertenece a :name.',
        'cancelled' => 'La reserva está cancelada: no se puede modificar.',
        'same_apartment' => 'La reserva ya está en ese depto.',
    ],

    'panel' => [
        'responsible' => 'Responsable: :name · cargada el :date',
        'stay_line' => 'Depto :number · :from al :to · :nights',
        'people' => 'Personas',
        'mattresses' => '{1} 1 colchón|[2,*] :count colchones',
        'capacity' => 'capacidad :count',
        'payment' => 'Pago',
        'condition' => 'Condición',
        'extras' => 'Extras',
        'no_extras' => 'Ninguno',
        'no_notes' => 'Sin nota.',
        'edit' => 'Editar',
        'move' => 'Mover de departamento',
        'cancel' => 'Cancelar reserva',
        'back' => 'Volver',
        'updated' => 'Cambios guardados.',
        'change_help' => 'Para cambiar fechas, personas o departamento se vuelve al buscador, que revisa la disponibilidad.',

        'move_title' => 'Mover',
        'move_to' => 'Mover al :number',
        'not_movable_help' => 'Esta reserva no es movible: el huésped pidió este depto o se le ofreció en particular.',
        'move_anyway' => 'Moverla igual',
        'free_for' => 'Libres del :from al :to para :people',
        'no_free' => 'No hay otro departamento libre para esas fechas.',
        'choose_apartment' => 'Elegí a qué depto moverla.',
        'moved' => 'Reserva movida al depto :to. El :from quedó libre del :dates.',

        'cancel_question' => '¿Cancelar esta reserva?',
        'cancel_help' => 'Queda guardada para control, pero deja de ocupar el depto :number del :from al :to.',
        'yes_cancel' => 'Sí, cancelar',
        'no_back' => 'No, volver',
        'cancelled_heading' => 'Reserva cancelada',
        'cancelled_by' => 'Cancelada por :name el :date. Ya no ocupa el depto :number.',
    ],
];
