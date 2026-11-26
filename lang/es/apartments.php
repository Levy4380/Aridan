<?php

return [
    'title' => 'Departamentos',
    'description' => ':active activos de :total. Un departamento con reservas futuras no se puede desactivar.',
    'new' => 'Nuevo departamento',
    'empty' => 'Todavía no hay departamentos cargados.',
    'edit_label' => 'Editar el depto :number',
    'people' => '{1} 1 persona|[2,*] :count personas',
    'created' => 'Depto :number creado.',
    'updated' => 'Depto :number guardado.',
    'cannot_deactivate' => '{1} No se puede desactivar: tiene 1 reserva futura. Primero movela o cancelala.|[2,*] No se puede desactivar: tiene :count reservas futuras. Primero movelas o cancelalas.',
    'future_reservations' => 'Reservas futuras',

    'delete' => [
        'button' => 'Eliminar departamento',
        'question' => '¿Eliminar el depto :number?',
        'help' => 'Se borra para siempre y no se puede deshacer. Si solo no se tiene que reservar más, desactivalo.',
        'with_reservations' => '{1} También se borra su reserva pasada o cancelada, con sus pagos.|[2,*] También se borran sus :count reservas pasadas o canceladas, con sus pagos.',
        'blocked' => '{1} No se puede eliminar: tiene 1 reserva futura o en curso. Primero movela o cancelala.|[2,*] No se puede eliminar: tiene :count reservas futuras o en curso. Primero movelas o cancelalas.',
        'confirm' => 'Sí, eliminar',
        'back' => 'No, volver',
        'deleted' => 'Depto :number eliminado.',
    ],

    'columns' => [
        'number' => 'Depto',
        'capacity' => 'Capacidad',
        'description' => 'Descripción',
        'status' => 'Estado',
    ],

    'modal' => [
        'create_title' => 'Nuevo departamento',
        'edit_title' => 'Depto :number',
        'create' => 'Crear departamento',
    ],

    'fields' => [
        'number' => 'Número',
        'capacity' => 'Capacidad (personas)',
        'description' => 'Descripción',
        'description_placeholder' => 'Con balcón, a la calle…',
        'sort_order' => 'Orden',
        'sort_order_help' => 'Posición en el calendario: menor va primero.',
        'active' => 'Activo',
        'active_help' => 'Solo los departamentos activos se pueden reservar.',
    ],
];
