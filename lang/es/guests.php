<?php

return [
    'optional' => 'opcional',

    'card' => [
        'phone' => 'Tel. :phone',
        'document' => 'DNI :document',
        'no_document' => 'DNI sin cargar',
        'reservations' => '{0} sin reservas|{1} 1 reserva|[2,*] :count reservas',
    ],

    'picker' => [
        'search' => 'Buscar por teléfono o DNI/CUIT',
        'select' => 'Elegir',
        'change' => 'Cambiar',
        'not_found' => 'No hay ningún cliente con ese teléfono o documento.',
        'new' => 'Cargar cliente nuevo',
        'new_help' => 'Si el teléfono o el DNI ya existen, te ofrecemos usar ese cliente en lugar de crear uno repetido.',
        'back_to_search' => 'Volver a buscar',
        'phone_owner' => 'Este teléfono ya pertenece a :name: ¿usar ese cliente?',
        'document_owner' => 'Este DNI/CUIT ya pertenece a :name: ¿usar ese cliente?',
        'use_existing' => 'Usar ese cliente',
    ],
];
