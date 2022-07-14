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

require_once __DIR__ . '/libs/Arrays.php';
require_once __DIR__ . '/libs/Url.php';
require_once __DIR__ . '/libs/ApiClient.php';
require_once __DIR__ . '/libs/Strings.php';
require_once __DIR__ . '/libs/Files.php';
require_once __DIR__ . '/libs/Debug.php';
require_once __DIR__ . '/libs/Orders.php';
require_once __DIR__ . '/libs/Date.php';
require_once __DIR__ . '/libs/DB.php';

require_once __DIR__ . '/helpers/debug.php';
require_once __DIR__ . '/helpers/cli.php';
#require __DIR__ . '/ajax.php';


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

		function ajaxCall(order_id){
			disable_button(order_id);

			jQuery.post(BASE_URL + '/wp-json/sinergia/v1/post?order_id=' + order_id)
			.done(function(res){
				//console.log('EXITO')
				//console.log(data)
				//console.log(res);

				const comprobante = res.data.comprobante;
				const pdf_url     = res.data.url_invoice;
				
				remove_button(order_id)
				add_invoice_link(order_id, comprobante, pdf_url)
			})
			.fail(function(xhr, status, error) {
				// error handling
				//console.log('FALLO')

				let res = xhr.responseJSON;
				let msg = res.message;

				// Alert o algo
				alert(msg);

				enable_button(order_id);

				//console.log(xhr.responseJSON);
				//console.log(status);
				//console.log(error);
			});
		}

		function sendRetry(event, order_id){
			event.stopImmediatePropagation();
			event.preventDefault();

			ajaxCall(order_id);
			return false;
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
	global $config;

	// Default
	$output = "<span id='$column-$order_id'></span>";

    switch($column)
    {
		case 'download-invoice-pdf':		
			// if (in_array($order_id, $fallidos)){
			// 	break;
			// }

			// $ruc_cliente  = Sinergia::get_ruc_from_order($order_id);

			// $tipo = empty($ruc_cliente) ? Sinergia::BOLETA : Sinergia::FACTURA;
		
			// $comprobante = DB::getComprobanteByOrderId($order_id, $tipo);

			// // Esto ya ser'ia un Error
			// if (empty($comprobante)){
			// 	break;
			// }

			// $pdf_url = Sinergia::getPdfUrl($comprobante);   
			// $anchor  = $comprobante;

			// $output = "<a href='$pdf_url' alt='pdf invoice $comprobante' target='_blank' id='$column-$order_id'>$anchor</a>";

			break;
		case 'gestionar-envio':

			/*
				El enlace debe hacer un llamado Ajax y
				la respuesta debe salir en un alert
			*/

			// if (in_array($order_id, $fallidos)){
			// 	// SEGUIR
			$output = "<button id='$column-$order_id' onclick='gestionarEnvio(event, $order_id);'>Enviar</button>";
			// }

			$order = Orders::getOrderById($order_id);

			// dd(
			// 	Orders::getCustomerData($order)
			// , 'CUSTOMER DATA');

			// redondeo a máximo N decimales
			$round = function($num){
				$num = (float) $num;
				return round($num, 5);
			};
			

			dd(
				Orders::getOrderItemArray($order), 'ITEMS'
			);
		

			break;
    }

	echo $output;
}




