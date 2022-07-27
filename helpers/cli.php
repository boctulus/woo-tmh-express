<?php

namespace boctulus\WooTMHExpress\helpers;

function is_cli(){
    return (php_sapi_name() == 'cli');
}