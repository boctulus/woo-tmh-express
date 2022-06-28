<?php

use boctulus\WooTMHExpress\libs\Debug;
use boctulus\WooTMHExpress\libs\Strings;
use boctulus\WooTMHExpress\libs\Products;
use boctulus\WooTMHExpress\libs\Files;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = include __DIR__ . '/config/config.php';

require_once __DIR__ . '/libs/Strings.php';


if (php_sapi_name() != "cli"){
	return; 
}

$file = $argv[0];
if (Strings::contains('/', $file)){
	$dir = Strings::beforeLast($file, '/');
	chdir($dir);
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', realpath(__DIR__ . '/../../..') . DIRECTORY_SEPARATOR);

	require_once ABSPATH . '/wp-config.php';
	require_once ABSPATH .'/wp-load.php';
}

require_once __DIR__ . '/libs/Debug.php';
require_once __DIR__ . '/libs/TGSync.php';
require_once __DIR__ . '/libs/Products.php';
require_once __DIR__ . '/libs/Mail.php';
require_once __DIR__ . '/libs/Files.php';

require_once __DIR__ . '/helpers/cli.php';


Products::deleteAllGaleryImages();
Products::deleteAllProducts();





