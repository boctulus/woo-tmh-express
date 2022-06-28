<?php declare(strict_types=1);

namespace boctulus\WooTMHExpress\libs;

/*
	@author boctulus
*/

class Arrays 
{
    /**
     * Gets the first key of an array
     *
     * @param array $array
     * @return mixed
     */
    static function array_key_first(array $arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }

    static function array_value_first(array $arr) {
        foreach($arr as $val) {
            return $val;
        }
        return NULL;
    }

    /**
     * nonassoc
     * Associative to non associative array
     * 
     * @param  array $arr
     *
     * @return array
     */
    static function nonassoc(array $arr){
        $out = [];
        foreach ($arr as $key => $val) {
            $out[] = [$key, $val];
        }
        return $out;
    }
 
    static function is_assoc(array $arr)
    {
        foreach(array_keys($arr) as $key){
            if (!is_int($key)) return true;
	            return false; 
        }		
    }

}

