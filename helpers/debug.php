<?php

use boctulus\WooTMHExpress\libs\Debug;

if (!function_exists('dd')){
	function dd($val, $msg = null, $pre_cond = null){
		Debug::dd($val, $msg, $pre_cond);
	}
}

if (!function_exists('here') && function_exists('dd')){
	function here(){
		Debug::dd('HERE');
	}
}

if (!function_exists('foo')){
	function foo(){
		throw new \Exception("FOO");
	}
}