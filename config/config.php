<?php

/*
    Woo TMH Express
    By boctulus
*/

if (!defined('TMH_SHIPPING_METHOD_LABEL')){
    define('TMH_SERVER_ERROR_MSG', 'Falla en el servidor, re-intente más tarde por favor. ');
    define('TMH_TODO_OK', 'Procesado exitosamente por TMH');
    define('TMH_SHIPPING_METHOD_LABEL', "TMH");  // el nombre de la transportadora *
    define('TMH_STATUS_IF_ERROR', 'processing');
    define('TMH_NO_DIM', "Hay productos sin dimensiones");
    define('TMH_SERVER_TIME_BEFORE_RETRY', 60);  // seconds
}

return [
    'shipping_cost'         => 2,  // valor arbitrario que deberia ser editable desde el Admin Panel
    'shipping_calc_tax'     => 'per_order',   // posibilidades: per_order | per_item
    'order_status_trigger'  => 'completed',
    'allowed_zip_codes_expiration_time' => 3600 * 24,

    'namespace' => 'boctulus\WooTMHExpress',

    /*
        Input en el checkout

        Si existe un campo similar en el checkout deberia poder especificarse y este no usarse

        Digamos si 'input_id_name' != 'id_num'
    */

    'input_id_name'       => 'id_num',
    'input_placeholder'   => 'Su ID',
    'input_required'      => true,
    'text_input_required' => 'El campo ID es requerido',
    'input_visibility'    => true,
    
    'url_base_endpoints' => 'https://tmhexpress.uc.r.appspot.com/api/',

    'endpoints' => [
        'create_order' => 'createOrder',
        'get_orders' => 'orders'
    ],

    'url_invoice_pdfs'  => 'https://tmhexpress.uc.r.appspot.com/print_guide',

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