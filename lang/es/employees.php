<?php

return [
    'title' => 'Empleados',
    'description' => 'Quienes cargan reservas. Cada uno tiene un color, y con ese color se pintan sus reservas en el calendario.',
    'new' => 'Nuevo empleado',
    'edit_label' => 'Editar a :name',
    'you' => 'vos',
    'created' => ':name ya puede entrar al sistema.',
    'updated' => 'Datos de :name guardados.',
    'cannot_deactivate_self' => 'No te podés desactivar a vos mismo. Pedíselo a otro empleado.',
    'swatch_taken_by' => 'lo usa :names',
    'preview' => 'Así se ven sus reservas',
    'preview_guest' => 'Familia Pérez',
    'preview_payment' => '3 pers. · Señado $100.000',

    'columns' => [
        'employee' => 'Empleado',
        'email' => 'Email (usuario)',
        'status' => 'Estado',
    ],

    'modal' => [
        'create_title' => 'Nuevo empleado',
        'create' => 'Crear empleado',
    ],

    'fields' => [
        'name' => 'Nombre',
        'email' => 'Email',
        'password' => 'Contraseña inicial',
        'password_help' => 'La puede cambiar después desde su perfil.',
        'color' => 'Color en el calendario',
        'color_help' => 'La letra sobre cada color indica quién ya lo usa.',
        'other_color' => 'Otro color',
        'active' => 'Activo',
        'active_help' => 'Si lo desactivás no puede entrar al sistema. Sus reservas se mantienen con su color.',
    ],

    'cli' => [
        'name' => 'Nombre y apellido',
        'email' => 'Email (para entrar)',
        'color' => 'Color en el calendario',
        'password' => 'Contraseña inicial',
        'created' => 'Empleado :name creado. Ya puede entrar con :email.',
    ],
];
