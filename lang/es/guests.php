<?php

return [
    'title' => 'Clientes',
    'description' => 'El teléfono es obligatorio y único. El DNI/CUIT es opcional, pero no se puede repetir.',
    'new' => 'Nuevo cliente',
    'create' => 'Crear cliente',
    'search' => 'Buscar por nombre, teléfono o DNI/CUIT',
    'empty' => 'No hay clientes que coincidan. Podés cargar uno nuevo.',
    'limited' => 'Se muestran los primeros :count. Usá el buscador para encontrar al resto.',
    'open_label' => 'Abrir la ficha de :name',
    'no_document' => 'Sin cargar',
    'since' => 'Cliente desde :date',
    'new_help' => 'Si el teléfono o el DNI ya existen, te avisamos en lugar de crear un cliente repetido.',
    'new_reservation' => 'Nueva reserva para este cliente',
    'history' => 'Historial de reservas',
    'history_apartment' => 'Depto :number · :people',
    'no_history' => 'Todavía no tiene reservas.',
    'created' => ':name ya está cargado.',
    'updated' => 'Datos de :name guardados.',
    'view' => 'Ver ficha',
    'optional' => 'opcional',

    'columns' => [
        'name' => 'Nombre',
        'phone' => 'Teléfono',
        'document' => 'DNI/CUIT',
        'reservations' => 'Reservas',
        'last_stay' => 'Última estadía',
    ],

    'errors' => [
        'phone_invalid' => 'Ingresá un teléfono válido.',
    ],

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
