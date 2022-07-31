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

require_once __DIR__ . '/libs/Debug.php';
require_once __DIR__ . '/libs/Strings.php';
require_once __DIR__ . '/libs/Orders.php';
require_once __DIR__ . '/libs/DB.php';

require_once __DIR__ . '/helpers/cli.php';


/*
	Mostrar todos los errores
*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


global $wpdb;

$cli = (php_sapi_name() == 'cli');


if (!$cli){
	echo "Ejecutar desde la terminal";
}


$order = Orders::getLastOrder();
Orders::addNote($order, 'Nueva notax', 'completed');



exit;
//

//Orders::setLastOrderStatus('on-hold');
Orders::setLastOrderStatus('processing');
Orders::setLastOrderStatus('completed');
