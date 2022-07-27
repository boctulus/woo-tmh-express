<?php

use boctulus\WooTMHExpress\libs\Debug;
use boctulus\WooTMHExpress\libs\Strings;
use boctulus\WooTMHExpress\libs\Date;
use boctulus\WooTMHExpress\libs\Files;
use boctulus\WooTMHExpress\libs\Mail;
use boctulus\WooTMHExpress\libs\WooTMHExpress;
use boctulus\WooTMHExpress\libs\Maps; ///

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

$address = 'Carlos B Zetina 138, Escandón I Seccion, Miguel Hidalgo, CDMX';

dd(
	Maps::getCoord($address)
);
