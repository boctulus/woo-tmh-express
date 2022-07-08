<?php

namespace boctulus\WooTMHExpress;

/*
Plugin Name: Woo TMH Express
Description: Plugin que permite administrar envios via TMH Express
Version: 1.0.0
Author: boctulus
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/*
	Evidenciar errores
*/


#if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY){
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
#}


include_once 'tmh_main.php';


