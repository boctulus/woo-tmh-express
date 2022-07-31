<?php

use boctulus\WooTMHExpress\libs\Debug;
use boctulus\WooTMHExpress\libs\Strings;
use boctulus\WooTMHExpress\libs\Orders;
use boctulus\WooTMHExpress\libs\Files;
use boctulus\WooTMHExpress\libs\Mail;
use boctulus\WooTMHExpress\libs\WooTMHExpress;

// ...


if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', realpath(__DIR__ . '/../../..') . DIRECTORY_SEPARATOR);

	require_once ABSPATH . '/wp-config.php';
	require_once ABSPATH .'/wp-load.php';
}


/*
	Mostrar todos los errores
*/

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ERROR | E_PARSE);


$cfg = \boctulus\WooTMHExpress\helpers\config();


$order = Orders::getLastOrder();

dd(
	Orders::getLastOrderNoteMessage($order, 'WooCommerce')
);


// $order_id = Orders::getLastOrderId();
//
// dd(
// 	WooTMHExpress::get_id_num_from_order($order_id)
// , "ID NUMBER for order_id = $order_id");


// dd(
// 	WooTMHExpress::get($cfg['endpoints']['get_orders'])
// );
