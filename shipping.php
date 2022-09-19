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
        WooTMHExpress::processOrder($order_id);    
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

            /*
                El calculo de costo 'per_item' no funciona. Solo 'per_order' 

                Parece bug en WooCommerce

                https://woocommerce.com/document/shipping-method-api/#section-5
            */

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


