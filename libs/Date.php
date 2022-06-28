<?php

/*
    @author  Pablo Bozzolo boctulus@gmail.com
*/

namespace boctulus\WooTMHExpress\libs;

use boctulus\WooTMHExpress\libs\Strings;
use boctulus\WooTMHExpress\libs\Arrays;

/*
    @author Pablo Bozzolo
*/
class Date
{
    static function at(string $format = 'Y-m-d H:i:s', $timezone = null){
        if ($timezone === null){	
            $timezone = new \DateTimeZone( date_default_timezone_get() );
        } else {
            if (is_string($timezone)){
                $timezone = new \DateTimeZone($timezone);
            }
        }
    
        $d  = new \DateTime('', $timezone);
        $at = $d->format($format); // ok
    
        return $at;
    }
}