<?php

use boctulus\WooTMHExpress\libs\Debug;
use boctulus\WooTMHExpress\libs\Strings;
use boctulus\WooTMHExpress\libs\Date;
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

// dd(
// 	WooTMHExpress::get($cfg['endpoints']['get_orders'])
// );

// exit;

/*
	Si es exitosa devuelve

	array (
		'data' =>
			array (
				'order_id' => 247,
				'guide' => 514181,
				'message' => 'La orden a sido creada de forma exitosa.',
			),
		'http_code' => 200,
		'errors' => '',
	)
*/
dd(
	WooTMHExpress::registrar([
		'origin' =>
		array (
			'address'   => $cfg['origin']['address'],
			'latitude'  => $cfg['origin']['latitude'],
			'longitude' => $cfg['origin']['longitude'],
		),

		/*
			Leer de la Orden y usar lib de geolocalizacion
		*/

		'destination' =>
		array (
			'address' => 'Carlos B Zetina 138, Escandón I Seccion, Miguel Hidalgo, CDMX',
			'latitude' => 19.402299677796147,
			'longitude' => -99.183665659261,
		),

		/*
			Leer de la Orden
		*/

		'contact' =>
		array (
			'number_identification' => 123456,
			'full_name' => 'John Smith',
			'phone' => '5512213456',
			'email' => 'john.smith@ivoy.mx',
		),
		'package' =>
		array (
			'dimensions' =>
			array (
			'volume' => 1,
			'pieces' => 1,
			'weight' => 1,
			),
			'containt' => 'Prueba',
			'type_product' => 'Documentos',
		),
	],$cfg['endpoints']['create_order'])
);