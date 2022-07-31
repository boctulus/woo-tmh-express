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
    $config = \boctulus\WooTMHExpress\helpers\config();

    //var_dump($order_id);
    //var_dump ([$old_status, $new_status]);

    if( $status_to == $config['order_status_trigger'] ) 
    {
        $notas = [];						
    
        $order = new \WC_Order($order_id);	
    
        if(!isset($_SESSION)) {
            session_start();
        }
        
        if (isset($_SESSION['server_not_before']) && (time() < $_SESSION['server_not_before'])){
            $order->update_status(TMH_STATUS_IF_ERROR, TMH_SERVER_ERROR_MSG . 'Code g500. Technical detail: waiting for server recovery');
            return;
        }
            
        $package = [];
        foreach ($order->get_items() as $item_key => $item )
        {
            //Files::localDump($item, 'ITEMS.txt');

            $item_id     = $item->get_id();
            $product_id  = Orders::orderItemId($order); 
            
            $meta = get_post_meta($product_id);
            
            $l = $meta['_length'][0] ?? null;
            $w = $meta['_width'][0]  ?? null;
            $h = $meta['_height'][0] ?? null;
            $W = $meta['_weight'][0] ?? null;
            $Q = $item->get_quantity();
             
            $package['dimensions'] = [
                'volume' => $l * $w * $h,
                'weight' => $W,
                'pieces' => $Q
            ];
            
            //debug([$l, $w, $h, $W], 'Dimmensions');
            //debug($meta, "meta para prod id = $item_id");
        }


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
        
        
        try {
            $recoleccion_res = WooTMHExpress::registrarEnvio($data['dest_address'], $data['customer'], $data['package']);

            //Files::localDump([$data['dest_address'], $data['customer'], $data['package']], 'THM-REQUEST.txt');
            //Files::localDump($recoleccion_res, 'THM-RESPONSE.txt');

            if ($recoleccion_res['http_code'] != 200){
                $error = "{$recoleccion_res['errors']} - HTTP CODE: {$recoleccion_res['http_code']}";
                $order->update_status(TMH_STATUS_IF_ERROR, TMH_SERVER_ERROR_MSG . 'Code r001. Technical detail: '. $error);

                return;
            }
        } catch (\Exception $e){
            $_SESSION['server_error_time'] = time();
            $_SESSION['server_not_before'] = $_SESSION['server_error_time'] + TMH_SERVER_TIME_BEFORE_RETRY;
            $order->update_status(TMH_STATUS_IF_ERROR, TMH_SERVER_ERROR_MSG . 'Code r002. Technical detail: '. $e->getMessage());
            return;
        }
                
        //debug(json_encode($data, JSON_PRETTY_PRINT)); exit; ///
        
        if (empty($recoleccion_res)){	
            //debug(json_encode($data, JSON_PRETTY_PRINT)); exit; ///				
            $order->update_status(TMH_STATUS_IF_ERROR, TMH_SERVER_ERROR_MSG. 'Code r002B');
            return; //
        }
        
        if (!isset($recoleccion['data']['guide'])){
            $order->update_status(TMH_STATUS_IF_ERROR, TMH_SERVER_ERROR_MSG.  'Code r003');
            return; //
        }	
        
        $order->update_status($config['order_status_trigger'], $shipping_note == null ? TMH_TODO_OK : $shipping_note);        
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


