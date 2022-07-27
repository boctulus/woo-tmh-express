<?php

namespace boctulus\WooTMHExpress\helpers;

spl_autoload_register( 'boctulus\WooTMHExpress\helpers\wp_namespace_autoload' );

function wp_namespace_autoload( $class ) {
 
    $namespace = config()['namespace'];
 
	if (strpos($class, $namespace) !== 0) {
		return;
	}
 
	$class = str_replace($namespace, '', $class);
	$class = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
 
	$directory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
	$path = $directory . DIRECTORY_SEPARATOR . $class;
 

    // If the file exists in the specified path, then include it.
    if ( file_exists( $path ) ) {
        include_once( $path );
    } else {
        throw new \Exception("The file attempting to be loaded at '$path' does not exist." );
    }

}