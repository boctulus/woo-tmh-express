<?php

use boctulus\WooTMHExpress\libs\Debug;
use boctulus\WooTMHExpress\libs\Files;

/*
Plugin Name: Woo TMH Express
Description: Plugin que permite administrar envios via TMH Express
Version: 1.0.1
Author: boctulus < boctulus@gmail.com >
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/*
	Evidenciar errores
*/


if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY){
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL & ~E_NOTICE);
}


include_once 'tmh_main.php';
include_once 'shipping.php';


