<?php

require_once __DIR__ . '/libs/Files.php';
require_once __DIR__ . '/libs/Strings.php';
require_once __DIR__ . '/libs/DB.php';
require_once __DIR__ . '/libs/Orders.php';
require_once __DIR__ . '/libs/Mail.php';

use boctulus\WooTMHExpress\libs\Files;
use boctulus\WooTMHExpress\libs\Strings;
use boctulus\WooTMHExpress\libs\DB;
use boctulus\WooTMHExpress\libs\Orders;
use boctulus\WooTMHExpress\libs\Mail;
use boctulus\WooTMHExpress\libs\WooTMHExpress;

/*
	REST

*/

function process_order(WP_REST_Request $req)
{
    global $wpdb;

    try {        
        $error = new WP_Error();

        $data = $req->get_body();

        if ($data === null){
            throw new \Exception("No se recibió la data");
        }

        $data = json_decode($data, true);

        if ($data === null){
            throw new \Exception("JSON inválido");
        }

        $order_id = $data['order_id'] ?? null;

        if (empty($order_id)){
            $error->add(400, 'order_id es requerido');
            return $error;
        }

        if (!Orders::orderExists($order_id)){
            $error->add(404, 'order_id no existe');
            return $error;
        }

        try {
            $sql   = "SELECT tracking_num, tmh_order_id FROM `{$wpdb->prefix}tmh_orders` WHERE `woo_order_id`=$order_id;";
            $row = $wpdb->get_row($sql, ARRAY_A);

            $tracking     = $row['tracking_num'];
            $tmh_order_id = $row['tmh_order_id'];
      
            /*
                Es un "error" pero no es "fatal" y por eso envio 200
            */

            if (!empty($tracking)){
                $res = [
                    'code' => 200,
                    'message' => "Orden ignorada. La orden '$order_id' ya fue procesada previamente",
                    'data' => [
                        "tracking_num" => $tracking,
                        "tmh_order_id" => $tmh_order_id,
                        "invoice_url"  => WooTMHExpress::get_pdf_invoice_url($tmh_order_id)
                    ]
                ];
        
                $res = new WP_REST_Response($res);
                $res->set_status(200);

                return $res;
            }

           $res = WooTMHExpress::processOrder($order_id);

           if (empty($res)){
                throw new \Exception("Error procesando orden '$order_id'");
           }
        } catch (\Exception $e){	
    
            // Log del error
            Files::logger($e->getMessage());

            $error->add(500, $e->getMessage());
            return $error;
        }
    
        $res = [
            'code' => 200,
            'message' => "Orden '$order_id' fue procesada exitosamente",
            'data' => [
                "tracking_num" => $res["tracking_num"],
                "tmh_order_id" => $res["tmh_order_id"],
                "invoice_url"  => $res["invoice_url"]
            ]
        ];

        $res = new WP_REST_Response($res);
        $res->set_status(200);

        return $res;
    } catch (\Exception $e) {
        $error = new WP_Error();
        $error->add(500, $e->getMessage());

        return $error;
    }
}

function dummy(){
    sleep(2);

    $res = new WP_REST_Response('OK');
    $res->set_status(200);

    return $res;
}


add_action('rest_api_init', function () {
    #	{VERB} /wp-json/xxx/v1/zzz
    register_rest_route('tmh_express/v1', '/process_order', array(
        'methods' => 'POST',
        'callback' => 'process_order',
        'permission_callback' => '__return_true'
    ));

    register_rest_route('tmh_express/v1', '/post_dummy', array(
        'methods' => 'POST',
        'callback' => 'dummy',
        'permission_callback' => '__return_true'
    ));
});
