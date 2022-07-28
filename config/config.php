<?php

/*
    Woo TMH Express
    By boctulus
*/

return [
    'namespace' => 'boctulus\WooTMHExpress',

    /*
        Input en el checkout

        Si existe un campo similar en el checkout deberia poder especificarse y este no usarse
    */

    'input_placeholder'   => 'Su ID',
    'input_required'      => true,
    'text_input_required' => 'El campo ID es requerido',
    'input_visibility'    => true,
    
    'url_base_endpoints' => 'https://tmhexpress.uc.r.appspot.com/api/',
    'endpoints' => [
        'create_order' => 'createOrder',
        'get_orders' => 'orders'
    ],

    /*
        Definido para el cliente 
        -->
    */

    'token' => 'iZjL8MujTP8nb1MbfYeCKm2OOBxgyEXY',

    'origin' => [
        'address'   => 'Carlos B Zetina 138, Escandón I Seccion, Miguel Hidalgo, CDMX',
        'latitude'  => 19.402299677796147,
        'longitude' => -99.183665659261
    ]
];