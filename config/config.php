<?php

/*
    Woo TMH Express
    By boctulus
*/

if (!defined('TMH_SERVER_ERROR_MSG')){
    define('TMH_SERVER_ERROR_MSG', 'Falla en el servidor, re-intente más tarde por favor. ');
    define('TMH_TODO_OK', 'Procesado exitosamente por TMH');
    define('TMH_STATUS_IF_ERROR', 'processing');
    define('TMH_NO_DIM', "Hay productos sin dimensiones");
    define('TMH_SERVER_TIME_BEFORE_RETRY', 60);  // seconds
}

return [
    /*
        Costo del envio
    */

    'shipping_cost'         => 2,

    /* 
        Como se aplican los costos de envio: por item (¨per_item¨) o por órden (¨per_order¨)
    */

    'shipping_calc_tax'     => 'per_item',   
    
    /*
        Condición que dispara comunicación con THM Express
    */

    'order_status_trigger'  => 'completed',

    /*
        Token provisto por TMH Express`
    */

    'token' => 'iZjL8MujTP8nb1MbfYeCKm2OOBxgyEXY',

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


    //
    // No editar nada desde aquí ----------------->
    //

    'allowed_zip_codes_expiration_time' => 3600 * 24,

    'namespace' => 'boctulus\WooTMHExpress',

    'url_base_endpoints' => 'https://tmhexpress.uc.r.appspot.com/api/',

    'endpoints' => [
        'create_order' => 'createOrder',
        'get_orders' => 'orders'
    ],

    'url_invoice_pdfs'  => 'https://tmhexpress.uc.r.appspot.com/print_guide',

    'origin' => [
        'address'   => 'Carlos B Zetina 138, Escandón I Seccion, Miguel Hidalgo, CDMX',
        'latitude'  => 19.402299677796147,
        'longitude' => -99.183665659261
    ],
];