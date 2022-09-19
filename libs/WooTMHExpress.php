<?php

/*
    @author  Pablo Bozzolo boctulus@gmail.com
*/

namespace boctulus\WooTMHExpress\libs;

use boctulus\WooTMHExpress\libs\Files;
use boctulus\WooTMHExpress\libs\Maps;


class WooTMHExpress
{  
    static function processOrder($order_id){
        global $wpdb;
        
        $order = new \WC_Order($order_id);	
    
        if(!isset($_SESSION)) {
            session_start();
        }

        $config = \boctulus\WooTMHExpress\helpers\config();
        
        # Files::dd(__LINE__ . ' - '. __FUNCTION__);

        if (isset($_SESSION['server_not_before']) && (time() < $_SESSION['server_not_before'])){
            $order->update_status(TMH_STATUS_IF_ERROR, TMH_SERVER_ERROR_MSG . 'Code g500. Technical detail: waiting for server recovery');

            $sql = "UPDATE `{$wpdb->prefix}tmh_orders` 
            SET try_count=try_count+1 
            WHERE woo_order_id=$order_id;";

            $wpdb->query($sql);

            return;
        }
            
        $package = [];
        
        $v_total = 0;
        $w_total = 0;
        $q_total = 0;

        foreach ($order->get_items() as $item_key => $item )
        {
            //Files::localDump($item, 'ITEMS.txt');

            $item_id     = $item->get_id();
            $product_id  = Orders::orderItemId($item); 
            
            $meta = get_post_meta($product_id);
            
            $l = $meta['_length'][0] ?? 0;  
            $w = $meta['_width'][0]  ?? 0;
            $h = $meta['_height'][0] ?? 0;
            $W = $meta['_weight'][0] ?? 0;
            $Q = $item->get_quantity();             

            $v_total += $l * $w * $h;
            $w_total += ($W * $Q);
            $q_total += $Q;
        }

        $package['dimensions'] = [
            'volume' => $v_total,
            'weight' => $w_total,
            'pieces' => $q_total
        ];

        /*
            Campos obligatorios (hardcodeados)
        */

        $package["containt"]     = "Prueba";
        $package["type_product"] = "Documentos";


        $timezone = date_default_timezone_get();        
        
        // Get the Customer billing email
        $billing_email  = $order->get_billing_email();

        // Get the Customer billing phone
        $billing_phone  = $order->get_billing_phone();
        
        // Customer billing information details
        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name  = $order->get_billing_last_name();
        $billing_company    = $order->get_billing_company();
        $billing_address_1  = $order->get_billing_address_1();
        $billing_address_2  = $order->get_billing_address_2();
        $billing_city       = $order->get_billing_city();
        $billing_state      = $order->get_billing_state();
        $billing_postcode   = $order->get_billing_postcode();
        $billing_country    = $order->get_billing_country();

        // Customer shipping information details
        $shipping_first_name = $order->get_shipping_first_name();
        $shipping_last_name  = $order->get_shipping_last_name();
        $shipping_company    = $order->get_shipping_company();
        $shipping_address_1  = $order->get_shipping_address_1();
        $shipping_address_2  = $order->get_shipping_address_2();
        $shipping_city       = $order->get_shipping_city();
        $shipping_state      = $order->get_shipping_state();
        $shipping_postcode   = $order->get_shipping_postcode();
        $shipping_country    = $order->get_shipping_country();
        $shipping_note       = NULL;
        
        //debug($order, 'ORDER OBJECT');
    
        # Files::dd(__LINE__ . ' - '. __FUNCTION__);
                    
        /*
            Cotizacion
        */
            
        $data = [
            "dest_address" => "$shipping_address_1, $shipping_address_2, $shipping_city, $shipping_country",
            "customer" => [
                "number_identification" => WooTMHExpress::get_id_num_from_order($order_id),
                "full_name" => "$shipping_last_name, $shipping_first_name ",
                "phone"     => $billing_phone,
                "email"     => $billing_email,
            ],
            "package"  => $package,
            "notes"    => $shipping_note
        ];		
        
        #Files::dd(__LINE__ . ' - '. __FUNCTION__);  /// no llega hasta aca_______!>????

        try {
            $full_addr = $data['dest_address'];
            $full_addr = str_replace(', ,', ', ', $full_addr);

            $recoleccion_res = WooTMHExpress::registrarEnvio($full_addr, $data['customer'], $data['package']);

            #Files::localDump([$data['dest_address'], $data['customer'], $data['package']], 'THM-REQUEST.txt');
            #Files::localDump($recoleccion_res, 'THM-RESPONSE.txt');

            if ($recoleccion_res['http_code'] != 200){
                $error = "{$recoleccion_res['errors']} - HTTP CODE: {$recoleccion_res['http_code']}. ";
                $order->update_status(TMH_STATUS_IF_ERROR, TMH_SERVER_ERROR_MSG . 'Code r001. Technical detail: '. $error);

                $sql = "UPDATE `{$wpdb->prefix}tmh_orders` 
                SET try_count=try_count+1 
                WHERE woo_order_id=$order_id;";
                
                $wpdb->query($sql);

                return;
            } 
        } catch (\Exception $e){
            $_SESSION['server_error_time'] = time();
            $_SESSION['server_not_before'] = $_SESSION['server_error_time'] + TMH_SERVER_TIME_BEFORE_RETRY;
            $order->update_status(TMH_STATUS_IF_ERROR, TMH_SERVER_ERROR_MSG . 'Code r002. Technical detail: '. $e->getMessage());

            $sql = "UPDATE `{$wpdb->prefix}tmh_orders` 
            SET try_count=try_count+1 
            WHERE woo_order_id=$order_id;";
            
            $wpdb->query($sql);

            return;
        }

        #Files::dd(__LINE__ . ' - '. __FUNCTION__);
        
        if (empty($recoleccion_res)){				
            $order->update_status(TMH_STATUS_IF_ERROR, TMH_SERVER_ERROR_MSG. 'Code r002B. Technical detail: respuesta vacia');

            $sql = "UPDATE `{$wpdb->prefix}tmh_orders` 
            SET try_count=try_count+1 
            WHERE woo_order_id=$order_id;";
            
            $wpdb->query($sql);

            return; //
        }
        
        if (!isset($recoleccion_res['data']['order_id'])){
            $order->update_status(TMH_STATUS_IF_ERROR, TMH_SERVER_ERROR_MSG.  'Code r003. Technical detail: tracking no encontrado');
            return; //
        }	

        $tracking      = $recoleccion_res['data']['guide']    ?? null;
        $tmh_order_id  = $recoleccion_res['data']['order_id'] ?? null;
        $msg           = $recoleccion_res['data']['message']  ?? null;

        if (empty($tmh_order_id)){
            $order->update_status(TMH_STATUS_IF_ERROR, TMH_SERVER_ERROR_MSG. 'Code r004. Technical detail: respuesta sin numero de guia');

            $sql = "UPDATE `{$wpdb->prefix}tmh_orders` 
            SET try_count=try_count+1 
            WHERE woo_order_id=$order_id;";
            
            $wpdb->query($sql);

            return; //
        }

        $shipping_note = "Guía: #{$tracking} - $msg";

        #Files::localLogger("$order_id - $shipping_note"); ////

        $sql = "UPDATE `{$wpdb->prefix}tmh_orders` 
        SET tmh_order_id=$tmh_order_id, tracking_num=$tracking 
        WHERE woo_order_id=$order_id;";
        
        //var_dump($sql). "\r\n\r\n";
        $wpdb->query($sql);
        
        $order->update_status($config['order_status_trigger'], $shipping_note);  
        
        return [
            'tracking_num' => $tracking,
            'tmh_order_id' => $tmh_order_id,
            'invoice_url'  => self::get_pdf_invoice_url($tmh_order_id)
        ];
    }

    // url para ver el PDF
    static function get_pdf_invoice_url($order_id){
        $config = \boctulus\WooTMHExpress\helpers\config();

        return "{$config['url_invoice_pdfs']}/$order_id";
    }

    static function get_id_num_from_order($order_id){
        $meta_key = 'id_num'; 
        return get_post_meta($order_id, $meta_key, true);
    }

    static function getClient($endpoint){
        if (empty($endpoint)){
            throw new \InvalidArgumentException("Endpoint es requerido");
        }

        $config = \boctulus\WooTMHExpress\helpers\config();

        $ruta  = $config['url_base_endpoints'] . $endpoint;
        $token = $config['token']; 

        if (\boctulus\WooTMHExpress\helpers\is_cli())
            dd($ruta, 'ENDPOINT *****');

        $client = (new ApiClient($ruta));

        $client
        ->setHeaders(
            [
                "Content-type"  => "Application/json",
                "Accept" => "application/json"
            ]
        )
        ->setJWTAuth($token)
        //->setRetries(3)
        ;

        #if ($config['dev_mode']){
            $client->disableSSL();
        #}        
        
        return $client;
    }

    /*
        General: a cualquier endpoint
    */
    static function registrar($data, $endpoint)
    {
        Files::localDump(
            [
                'url' => $endpoint,
                'body' => $data
            ], 'req.txt'); /////////////

        $response = static::getClient($endpoint)
        ->setBody($data)
        ->post()
        ->getResponse();

        Files::localDump($response, 'res.txt');  ///////////

        return $response;
    }

    static function get($endpoint)
    {
        $response = static::getClient($endpoint)
        ->get()
        ->getResponse()
        ;

        return $response;
    }

    /*
        Ej:

        $dest_address

        'Carlos B Zetina 138, Escandón I Seccion, Miguel Hidalgo, CDMX'

        $customer_data 

		array (
			'number_identification' => 123456,
			'full_name' => 'John Smith',
			'phone' => '5512213456',
			'email' => 'john.smith@ivoy.mx',
		)
		
        $package_data 

		array (
			'dimensions' =>
                array (
                    'volume' => 1,
                    'pieces' => 1,
                    'weight' => 1,
                ),
			'containt' => 'Prueba',
			'type_product' => 'Documentos',
		),
    */
    static function registrarEnvio($dest_address, $customer_data, $package_data){
        $cfg = \boctulus\WooTMHExpress\helpers\config();

        $geo_ay = Maps::getCoord($dest_address);

        // podria fallar si la direccion tiene un problema

        return static::registrar([
            'origin' =>
            array (
                'address'   => $cfg['origin']['address'],
                'latitude'  => $cfg['origin']['latitude'],
                'longitude' => $cfg['origin']['longitude'],
            ),
    
            /*
                Leer de la Orden y usar lib de geolocalizacion +++++++++++++
            */
    
            'destination' =>
            array (
                'address'   => $dest_address,
                'latitude'  => $geo_ay['lat'] ?? "0",
                'longitude' => $geo_ay['lon'] ?? "0",
            ),
    
            /*
                Leer de la Orden
            */
    
            'contact' => $customer_data,
            'package' => $package_data,
        ],$cfg['endpoints']['create_order']);
    }

    static function getPostalCodes(){
        $zip_codes = get_transient('tmh_allowed_zip_codes');

        if (!empty($zip_codes)){         
            return $zip_codes;
        }

        $config = \boctulus\WooTMHExpress\helpers\config();

        $res = static::get('codePostal');
        
        if ($res['http_code'] != 200){
            $error = "Error al obtener codigos postales via endpoint. Code: {$res['http_code']}.";

            if (!empty($res['errors'])){
                $error .= $res['errors'];
            }

            Files::localLogger($error);

            return false;
        }

        if (empty($res['data'])){
            Files::localLogger("No se encontraron codigos postales via endpoint");
            return false;
        }

        $zip_codes = array_column($res['data'], 'code'); 
        set_transient('tmh_allowed_zip_codes', $zip_codes, $config['allowed_zip_codes_expiration_time']);

        
        return $zip_codes;
    }

}