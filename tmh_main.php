<?php

namespace boctulus\WooTMHExpress;

/*
	Woo TMH Express
	@author boctulus
*/

use boctulus\WooTMHExpress\libs\Arrays;
use boctulus\WooTMHExpress\libs\Url;
use boctulus\WooTMHExpress\libs\ApiClient;
use boctulus\WooTMHExpress\libs\Strings;
use boctulus\WooTMHExpress\libs\Files;
use boctulus\WooTMHExpress\libs\Debug;
use boctulus\WooTMHExpress\libs\Orders;
use boctulus\WooTMHExpress\libs\Date;
use boctulus\WooTMHExpress\libs\DB;
use boctulus\WooTMHExpress\libs\WooTMHExpress;
use ParagonIE\Sodium\Core\Curve25519\Ge\P2;

require_once __DIR__ . '/helpers/config.php'; 
require_once __DIR__ . '/helpers/autoloader.php'; // *
require_once __DIR__ . '/helpers/debug.php';
require_once __DIR__ . '/helpers/cli.php';

require_once __DIR__ . '/id_to_checkout.php'; // agrega metabox con campo de identificacion
require_once __DIR__ . '/installer/tmh_orders.php';

#if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY){
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
#}


if (isset($_GET['post_type']) && $_GET['post_type'] == 'shop_order'):
	?>
	<script>
		const BASE_URL = '<?= get_site_url() ?>';
		const PLUGIN_URL = '<?= __DIR__ ?>';

		// Revisar las siguientes funciones. Son necesarias?

		function add_invoice_link(order_id, comprobante, pdf_url){
			jQuery('#download-invoice-pdf-' + order_id).replaceWith('<a href="'+ pdf_url +'" target="_blank">'+ comprobante +'</a>')
		}

		function remove_button(order_id){
			jQuery('#gestionar-envio-' + order_id).replaceWith('<span id="#gestionar-envio-' + order_id + '"></span>')
		}

		function disable_button(order_id){
			jQuery('#gestionar-envio-' + order_id).prop("disabled",true)
		}

		function enable_button(order_id){
			jQuery('#gestionar-envio-' + order_id).prop("disabled",false)
		}
		
	</script>
<?php
endif;

// ADDING 2 NEW COLUMNS WITH THEIR TITLES
add_filter( 'manage_edit-shop_order_columns', 'boctulus\WooTMHExpress\custom_shop_order_column', 20 );
function custom_shop_order_column($columns)
{
    $reordered_columns = array();

    // Inserting columns to a specific location
    foreach( $columns as $key => $column){
        $reordered_columns[$key] = $column;
        if( $key ==  'order_status' ){
            // Inserting after "Status" column
           
			$reordered_columns['gestionar-envio'] = __( 'Gestionar envio','theme_domain');
            $reordered_columns['download-invoice-pdf'] = __( 'Descargar PDF','theme_domain');
        }
    }
    return $reordered_columns;
}


// Adding the data for the additional column
add_action( 'manage_shop_order_posts_custom_column' , 'boctulus\WooTMHExpress\custom_orders_list_column_content', 10, 2 );
function custom_orders_list_column_content( $column, $order_id )
{
	global $config, $wpdb;

	$order           = Orders::getOrderById($order_id);
	$shipping_method = $order->get_shipping_method();

	if ($shipping_method != TMH_SHIPPING_METHOD_LABEL){
		return;
	}

	$sql   = "SELECT COUNT(*) as count FROM `{$wpdb->prefix}tmh_orders` WHERE `woo_order_id`=$order_id;";
    $count = $wpdb->get_var($sql);

	// Que la orden no este en la tabla tmh_orders a estas alturas seria raro pero en pruebas claro que puede suceder
	// Esto resuelve inconcistencias
	if ($count == 0){
		return;
	}

	// Sino tmh_order_id esta vacio => es porque no se intento la comunicacion con TMH o esta fallo
	// Si es que fallo => try_count >0

	$sql  = "SELECT * FROM `{$wpdb->prefix}tmh_orders` WHERE `woo_order_id`=$order_id;";
    $row  = $wpdb->get_row($sql, ARRAY_A);

	$tmh_order_id = $row['tmh_order_id'];
	$try_count    = $row['try_count'];
	$tracking     = $row['tracking_num'];

	$has_failed   = ($tmh_order_id == null) && ($try_count == 0);


	// Default
	$output = "<span id='$column-$order_id'></span>";

    switch($column)
    {
		case 'download-invoice-pdf':		
			if ($tmh_order_id !== null){
				$pdf_url = WooTMHExpress::get_pdf_invoice_url($tmh_order_id);
				$anchor  = 'Recibo de TMH';
				$output = "<a href=\"$pdf_url\" alt=\"pdf invoice for tracking '$tracking'\" target=\"_blank\" id=\"$column-$order_id\">$anchor</a>";
			}
		break;
		case 'gestionar-envio':
			if ($has_failed){
				$title  = Orders::getLastOrderNoteMessage($order_id, 'WooCommerce');
				$output = "<button id='$column-$order_id' onclick='reintentarRegistro(event, $order_id);' title='$title'>Re-intentar</button>";
			}

			// $order = Orders::getOrderById($order_id);

			// dd(
			// 	Orders::getOrderItemArray($order), 'ITEMS'
			// );
		
		break;
    }

	echo $output;
}




