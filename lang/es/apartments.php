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
