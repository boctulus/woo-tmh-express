<?php

/*
    @author  Pablo Bozzolo boctulus@gmail.com
*/

namespace boctulus\WooTMHExpress\libs;

use boctulus\WooTMHExpress\libs\Files;
use boctulus\WooTMHExpress\libs\DB;

require_once __DIR__ . '/Files.php';
require_once __DIR__ . '/DB.php';

require_once __DIR__ . '/../helpers/debug.php';
require_once __DIR__ . '/../helpers/cli.php';

class WooTMHExpress
{  
    static function getClient($endpoint){
        if (empty($endpoint)){
            throw new \InvalidArgumentException("Endpoint es requerido");
        }

        $config = require __DIR__ . '/../config.php';

        $ruta  = $config['url_base_endpoints'] . $endpoint;
        $token = $config['token']; 

        if (is_cli())
            dd($ruta, 'ENDPOINT *****');

        $client = (new ApiClient($ruta));

        $client
        ->setHeaders(
            [
                "Content-type"  => "Application/json",
                "Accept" => "application/json"
            ]
        )
        ->setJWTAuth($token)
        //->setRetries(3)
        ;

        #if ($config['dev_mode']){
            $client->disableSSL();
        #}        
        
        return $client;
    }

    /*
        General: a cualquier endpoint
    */
    static function registrar($data, $endpoint)
    {
        $response = static::getClient($endpoint)
        ->setBody($data)
        ->post()
        ->getResponse();

        return $response;
    }

    static function get($endpoint)
    {
        $response = static::getClient($endpoint)
        ->get()
        //->getResponse()
        ;

        return $response;
    }

}