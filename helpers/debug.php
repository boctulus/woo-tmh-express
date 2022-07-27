<?php

namespace boctulus\WooTMHExpress\helpers;

use boctulus\WooTMHExpress\libs\Debug;


function dd($val, $msg = null, $pre_cond = null){
	Debug::dd($val, $msg, $pre_cond);
}

function here(){
	Debug::dd('HERE');
}

function foo(){
	throw new \Exception("FOO");
}
