<?php

if (!function_exists('is_cli')){
    function is_cli(){
        return (php_sapi_name() == 'cli');
    }
}