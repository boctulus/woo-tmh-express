<?php

use boctulus\WooTMHExpress\libs\Debug;
use boctulus\WooTMHExpress\libs\Strings;
use boctulus\WooTMHExpress\libs\Orders;
use boctulus\WooTMHExpress\libs\Files;
use boctulus\WooTMHExpress\libs\Mail;
use boctulus\WooTMHExpress\libs\Maps;
use boctulus\WooTMHExpress\libs\Products;
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

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL); // E_ERROR | E_PARSE | E_WARNING | E_NOTICE

Files::printLogs();

// $cfg = \boctulus\WooTMHExpress\helpers\config();

// dd(
// 	WooTMHExpress::getPostalCodes()
// );


Debug::dd(
	Maps::getCoord('Diego de Torres 5, Acala de Henaes, Madrid')
);


// $order = Orders::getLastOrder();

// dd(
// 	Orders::getLastOrderNoteMessage($order, 'WooCommerce')
// );


// $order_id = Orders::getLastOrderId();
//
// dd(
// 	WooTMHExpress::get_id_num_from_order($order_id)
// , "ID NUMBER for order_id = $order_id");


// dd(
// 	WooTMHExpress::get($cfg['endpoints']['get_orders'])
// );


function delete_external_products(){    
    $prods = Products::getAllProducts();

    foreach ($prods as $prod){
        if (Products::isExternal($prod)){
            Products::deleteProduct($prod);

            Debug::dd("Deleting product with id = ". $prod->id);
        }
    }
}


function all_products_must_have_price(){
	$prods = Products::getAllProducts();

	foreach ($prods as $prod){
		if ($prod->get_regular_price() == '0'){
			Products::updatePrice($prod->get_id(), 100);
		}

		Debug::dd($prod->get_regular_price(), "Prod {$prod->get_id()}");
	}
}


function all_products_must_have_dimmensions(){
	$prods = Products::getAllProducts();

	foreach ($prods as $product){
		$product->set_weight(rand(1, 20) * 0.5);
		$product->set_length(rand(1, 10));
		$product->set_width(rand(1, 10));
		$product->set_height(rand(1, 10));
		$product->save(); // es necesario

		Debug::dd("Peso y dimensiones seteadas para producto con PID = {$product->get_id()} \r\n");
	}
}


#all_products_must_have_price();
#all_products_must_have_dimmensions();