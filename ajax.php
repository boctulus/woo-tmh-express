<?php

require_once __DIR__ . '/libs/Files.php';
require_once __DIR__ . '/libs/Strings.php';
require_once __DIR__ . '/libs/DB.php';
require_once __DIR__ . '/libs/Sinergia.php';
require_once __DIR__ . '/libs/Mail.php';

use boctulus\WooTMHExpress\libs\Files;
use boctulus\WooTMHExpress\libs\Strings;
use boctulus\WooTMHExpress\libs\DB;
use boctulus\WooTMHExpress\libs\Sinergia;
use boctulus\WooTMHExpress\libs\Mail;
use ParagonIE\Sodium\Core\Curve25519\Ge\P2;

/*
	REST

*/

function register_at_tmh_express(WP_REST_Request $req)
{
    // try {
        
    //     $error = new WP_Error();

        // if (empty($order_id)){
        //     $error->add(400, 'order_id es requerido');
        //     return $error;
        // }

        // if (!DB::orderExists($order_id)){
        //     $error->add(404, 'order_id no existe en la cola');
        //     return $error;
        // }

        // try {
        //     Sinergia::homologar($order_id);

        //     // No debe llegar hasta ac'a si homologar falla con Excepcion
        //     $ok = DB::orderDelete($order_id, 'FAIL');
        // } catch (\Exception $e){	
    
        //     // Log del error
        //     Files::logger($e->getMessage());

        //     $error->add(500, $e->getMessage());
        //     return $error;
        // }
        

        // $ruc_cliente  = Sinergia::get_ruc_from_order($order_id);

        // $tipo = empty($ruc_cliente) ? Sinergia::BOLETA : Sinergia::FACTURA;
    
        // $comprobante = DB::getComprobanteByOrderId($order_id, $tipo);

        // // Esto ya ser'ia un Error
        // if (empty($comprobante)){
        //     $error->add(404, "Comprobante para order_id = $order_id no existe");
        //     return $error;
        // }

        // $pdf_url = Sinergia::getPdfUrl($comprobante);   
        

    //     $res = [
    //         'code' => 200,
    //         'message' => 'ok',
    //         'data' => [
    //             'comprobante' => $comprobante,
    //             'url_invoice' => $pdf_url,
    //         ]
    //     ];

    //     $res = new WP_REST_Response($res);
    //     $res->set_status(200);

    //     return $res;
    // } catch (\Exception $e) {
    //     $error = new WP_Error();
    //     $error->add(500, $e->getMessage());

    //     return $error;
    // }
}

function dummy(){
    sleep(2);

    $res = new WP_REST_Response('OK');
    $res->set_status(200);

    return $res;
}


add_action('rest_api_init', function () {
    #	{VERB} /wp-json/xxx/v1/zzz
    register_rest_route('tmh_express/v1', '/post', array(
        'methods' => 'POST',
        'callback' => 'register_at_tmh_express',
        'permission_callback' => '__return_true'
    ));

    register_rest_route('tmh_express/v1', '/dummy', array(
        'methods' => 'POST',
        'callback' => 'dummy',
        'permission_callback' => '__return_true'
    ));
});
