<?php

/*
    Woo TMH Express
    By boctulus
*/

if (!defined('TMH_SERVER_ERROR_MSG')){
    define('TMH_SERVER_ERROR_MSG', 'Falla en el servidor, re-intente más tarde por favor. ');
    define('TMH_TODO_OK', 'Procesado exitosamente por TMH');
    define('TMH_NO_DIM', "Hay productos sin dimensiones");
    define('TMH_THE_COURIER', 'TMH Express');
    define('TMH_RETRY_TEXT', 'Re-intentar');
    define('TMH_SERVER_TIME_BEFORE_RETRY', 2);  // seconds
}

return [
    /*
        Costo del envio
    */

    'tmh_shipping_cost'         => 50, 

    /* 
        Como se aplican los costos de envio: por item (¨per_item¨) o por órden (¨per_order¨)
    */

    'shipping_calculation'     => 'per_order',   
    
    /*
        Condición que dispara comunicación con THM Express

        Estados a los que podria pasar la orden: 'processing', 'completed'
    */

    'order_status_trigger'  => 'completed',

    'order_status_error'    => 'processing',

    /*
        Token provisto por TMH Express`
    */

    'token' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',

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

    'url_base_endpoints' => 'https://tmhexpress.com.mx/api/',

    'endpoints' => [
        'create_order' => 'createOrder',
        'get_orders' => 'orders'
    ],

    'url_invoice_pdfs'  => 'https://tmhexpress.com.mx/print_guide',

    'origin' => [
        'address'   => 'Carlos B Zetina 138, Escandón I Seccion, Miguel Hidalgo, CDMX',
        'latitude'  => 19.402299677796147,
        'longitude' => -99.183665659261
    ],
];