<?php

return [
    'title' => 'Pendientes a facturar',
    'description' => 'Pagos cobrados (de reservas señadas o pagadas) que todavía no se facturaron. Cada pago se factura por separado. Las canceladas también aparecen: el cobro existió.',
    'include_cash' => 'Incluir pagos en efectivo',
    'summary' => '{0} Nada pendiente.|{1} 1 pago · :total cobrados sin facturar|[2,*] :count pagos · :total cobrados sin facturar',
    'empty' => 'No hay pagos pendientes de facturar.',
    'open_label' => 'Facturar el pago de :guest',
    'stay' => 'Depto :number · :from al :to',
    'no_method' => 'Falta tipo de pago',
    'marked' => 'Pago de :guest marcado como facturado.',

    'columns' => [
        'guest' => 'Cliente',
        'document' => 'DNI/CUIT',
        'stay' => 'Reserva',
        'payment' => 'Estado',
        'method' => 'Tipo de pago',
        'amount' => 'Monto del pago',
    ],

    'drawer' => [
        'title' => 'Facturar',
        'amount' => 'Monto del pago',
        'other_payments' => '{1} La reserva tiene otro pago: :total cobrados en total.|[2,*] La reserva tiene :count pagos más: :total cobrados en total.',
        'document' => 'CUIT / DNI',
        'guest' => 'Cliente',
        'no_document_help' => 'El cliente no tiene DNI/CUIT cargado. Cargalo en su ficha antes de facturar.',
        'view_reservation' => 'Ver reserva',
        'mark' => 'Facturado',
    ],
];
