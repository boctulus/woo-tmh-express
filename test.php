<?php

use boctulus\WooTMHExpress\libs\Debug;
use boctulus\WooTMHExpress\libs\Sinergia;
use boctulus\WooTMHExpress\libs\Strings;
use boctulus\WooTMHExpress\libs\Date;
use boctulus\WooTMHExpress\libs\Files;
use boctulus\WooTMHExpress\libs\Mail;
use boctulus\WooTMHExpress\libs\DB;

// ...


if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', realpath(__DIR__ . '/../../..') . DIRECTORY_SEPARATOR);

	require_once ABSPATH . '/wp-config.php';
	require_once ABSPATH .'/wp-load.php';
}


require_once __DIR__ . '/libs/Debug.php';
#require_once __DIR__ . '/libs/Strings.php';
#require_once __DIR__ . '/libs/Date.php';
require_once __DIR__ . '/libs/Files.php';
require_once __DIR__ . '/libs/Mail.php';
require_once __DIR__ . '/libs/DB.php';

require_once __DIR__ . '/helpers/cli.php';

/*
	Mostrar todos los errores
*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR | E_PARSE);

