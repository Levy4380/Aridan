<?php

/*
|--------------------------------------------------------------------------
| Validation messages
|--------------------------------------------------------------------------
|
| Spanish (Argentina) versions of the framework's validation messages. Rules
| not listed here fall back to English.
|
*/

return [
    'accepted' => 'Tenés que aceptar :attribute.',
    'after' => ':Attribute tiene que ser una fecha posterior a :date.',
    'after_or_equal' => ':Attribute tiene que ser una fecha igual o posterior a :date.',
    'alpha_num' => ':Attribute solo puede tener letras y números.',
    'array' => ':Attribute tiene que ser una lista.',
    'before' => ':Attribute tiene que ser una fecha anterior a :date.',
    'before_or_equal' => ':Attribute tiene que ser una fecha igual o anterior a :date.',
    'between' => [
        'numeric' => ':Attribute tiene que estar entre :min y :max.',
        'string' => ':Attribute tiene que tener entre :min y :max caracteres.',
        'array' => ':Attribute tiene que tener entre :min y :max elementos.',
    ],
    'boolean' => ':Attribute tiene que ser verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'current_password' => 'La contraseña no es correcta.',
    'date' => ':Attribute no es una fecha válida.',
    'decimal' => ':Attribute tiene que tener :decimal decimales.',
    'different' => ':Attribute y :other tienen que ser distintos.',
    'digits' => ':Attribute tiene que tener :digits dígitos.',
    'digits_between' => ':Attribute tiene que tener entre :min y :max dígitos.',
    'email' => ':Attribute tiene que ser un email válido.',
    'exists' => ':Attribute no existe.',
    'gt' => [
        'numeric' => ':Attribute tiene que ser mayor que :value.',
    ],
    'gte' => [
        'numeric' => ':Attribute tiene que ser mayor o igual que :value.',
    ],
    'in' => ':Attribute no es válido.',
    'integer' => ':Attribute tiene que ser un número entero.',
    'lowercase' => ':Attribute tiene que estar en minúsculas.',
    'lt' => [
        'numeric' => ':Attribute tiene que ser menor que :value.',
    ],
    'lte' => [
        'numeric' => ':Attribute tiene que ser menor o igual que :value.',
    ],
    'max' => [
        'numeric' => ':Attribute no puede ser mayor que :max.',
        'string' => ':Attribute no puede tener más de :max caracteres.',
        'array' => ':Attribute no puede tener más de :max elementos.',
    ],
    'min' => [
        'numeric' => ':Attribute tiene que ser al menos :min.',
        'string' => ':Attribute tiene que tener al menos :min caracteres.',
        'array' => ':Attribute tiene que tener al menos :min elementos.',
    ],
    'not_in' => ':Attribute no es válido.',
    'numeric' => ':Attribute tiene que ser un número.',
    'password' => [
        'letters' => ':Attribute tiene que tener al menos una letra.',
        'mixed' => ':Attribute tiene que tener al menos una mayúscula y una minúscula.',
        'numbers' => ':Attribute tiene que tener al menos un número.',
        'symbols' => ':Attribute tiene que tener al menos un símbolo.',
        'uncompromised' => 'Esta :attribute apareció en una filtración de datos. Elegí otra.',
    ],
    'present' => ':Attribute tiene que estar presente.',
    'prohibited' => ':Attribute no está permitido.',
    'regex' => 'El formato de :attribute no es válido.',
    'required' => ':Attribute es obligatorio.',
    'required_if' => ':Attribute es obligatorio cuando :other es :value.',
    'required_with' => ':Attribute es obligatorio cuando hay :values.',
    'same' => ':Attribute y :other tienen que coincidir.',
    'size' => [
        'numeric' => ':Attribute tiene que ser :size.',
        'string' => ':Attribute tiene que tener :size caracteres.',
    ],
    'string' => ':Attribute tiene que ser texto.',
    'unique' => 'Ya existe otro registro con ese :attribute.',

    /*
    |--------------------------------------------------------------------------
    | Custom messages and attribute names
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'color' => [
            'regex' => 'El color tiene que ser un hexadecimal como #4fc3f7.',
        ],
        'email' => [
            'unique' => 'Ya hay un empleado con ese email.',
        ],
    ],

    'attributes' => [
        'name' => 'nombre',
        'email' => 'email',
        'password' => 'contraseña',
        'current_password' => 'contraseña actual',
        'password_confirmation' => 'confirmación de la contraseña',
        'color' => 'color',
    ],
];
