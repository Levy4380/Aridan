<?php

return [
    'title' => 'Pendientes a facturar',
    'description' => 'Reservas con plata cobrada (señadas o pagadas) que todavía no se facturaron. Las canceladas también aparecen: el cobro existió.',
    'include_cash' => 'Incluir pagos en efectivo',
    'summary' => '{0} Nada pendiente.|{1} 1 reserva · :total cobrados sin facturar|[2,*] :count reservas · :total cobrados sin facturar',
    'empty' => 'No hay reservas pendientes de facturar.',
    'open_label' => 'Facturar la reserva de :guest',
    'stay' => 'Depto :number · :from al :to',
    'no_method' => 'Falta tipo de pago',
    'marked' => 'Reserva de :guest marcada como facturada.',

    'columns' => [
        'guest' => 'Cliente',
        'document' => 'DNI/CUIT',
        'stay' => 'Reserva',
        'payment' => 'Estado',
        'method' => 'Tipo de pago',
        'amount' => 'Monto recibido',
    ],

    'drawer' => [
        'title' => 'Facturar',
        'amount' => 'Monto total recibido',
        'document' => 'CUIT / DNI',
        'guest' => 'Cliente',
        'no_document_help' => 'El cliente no tiene DNI/CUIT cargado. Cargalo en su ficha antes de facturar.',
        'view_reservation' => 'Ver reserva',
        'mark' => 'Facturada',
    ],
];
