<?php

use boctulus\WooTMHExpress\libs\WooTMHExpress;
use boctulus\WooTMHExpress\libs\Orders;
use boctulus\WooTMHExpress\libs\Files;

// https://woocommerce.com/document/shipping-method-api/

/**
 * Check if WooCommerce is active
 */

if (! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}

add_action( 'woocommerce_order_status_changed','after_place_order', 10, 3);

function after_place_order($order_id, $status_from, $status_to)
{
    global $wpdb;

    $order           = Orders::getOrderById($order_id);
	$shipping_method = $order->get_shipping_method();

	if ($shipping_method != TMH_SHIPPING_METHOD_LABEL){
		return;
	}

    $config = \boctulus\WooTMHExpress\helpers\config();

    $sql   = "SELECT COUNT(*) as count FROM `{$wpdb->prefix}tmh_orders` WHERE `woo_order_id`=$order_id;";
    $count = $wpdb->get_var($sql);
    
    if ($count == 0){
        $sql = "INSERT INTO `{$wpdb->prefix}tmh_orders` (`woo_order_id`) VALUES ($order_id);";
        $wpdb->query($sql);
    }

    # Files::dd(__LINE__ . ' - '. __FUNCTION__);

    if( $status_to == $config['order_status_trigger'] ) 
    {
        $notas = [];						
    
        $order = new \WC_Order($order_id);	
    
        if(!isset($_SESSION)) {
            session_start();
        }
        
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
    }	
}


add_action( 'woocommerce_shipping_init', 'tmh_shipping_method_init' );

function tmh_shipping_method_init() 
{
    class WC_TMH_Shipping_Method extends WC_Shipping_Method {
        /**
         * Constructor for your shipping class
         *
         * @access public
         * @return void
         */
        public function __construct() {
            $this->id                 = 'tmh_shipping_method'; 
            $this->method_title       = __( 'TMH' ); 
            $this->method_description = __( 'Integración con TMH' ); 

            $this->enabled            = "yes"; 
            $this->title              = "TMH Express"; 

            $this->init();
        }

        /**
         * Init your settings
         *
         * @access public
         * @return void
         */
        function init() {
            // Load the settings API
            $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
            $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

            // Save settings in admin if you have any defined
            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        /**
         *   function.
         *
         * @access public
         * @param mixed $package
         * @return void
         */
        public function calculate_shipping( $package = array()) {
            $config = \boctulus\WooTMHExpress\helpers\config();

            $zip_code = $package[ 'destination' ][ 'postcode' ];

            if (!in_array($zip_code, WooTMHExpress::getPostalCodes())){
                #Files::localLogger("El zip code '$zip_code' no es manejado por TMH");
                return false; ////////////////////////
            }

            // Files::dd(
            //     $zip_code, 'ZIP CODE'    
            // ); ////////////////////////////////

            $rate = array(
                'label'    => TMH_SHIPPING_METHOD_LABEL,
                'cost'     => $config['shipping_cost'],
				'calc_tax' => $config['shipping_calc_tax']
            );

            // Register the rate
            $this->add_rate( $rate );
        }


    } // end class
}



add_filter( 'woocommerce_shipping_methods', 'add_tmh_shipping_method' );

function add_tmh_shipping_method( $methods ) {
    $methods['tmh_shipping_method'] = 'WC_TMH_Shipping_Method';
    return $methods;
}


