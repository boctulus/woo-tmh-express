<?php

/*
    @author  Pablo Bozzolo boctulus@gmail.com
*/

namespace boctulus\WooTMHExpress\libs;

use boctulus\WooTMHExpress\libs\Files;
use boctulus\WooTMHExpress\libs\Maps;


class WooTMHExpress
{  
    // url para ver el PDF
    static function get_pdf_invoice_url($order_id){
        $config = \boctulus\WooTMHExpress\helpers\config();

        return "{$config['url_invoice_pdfs']}/$order_id";
    }

    static function get_id_num_from_order($order_id){
        $meta_key = 'id_num'; 
        return get_post_meta($order_id, $meta_key, true);
    }

    static function getClient($endpoint){
        if (empty($endpoint)){
            throw new \InvalidArgumentException("Endpoint es requerido");
        }

        $config = \boctulus\WooTMHExpress\helpers\config();

        $ruta  = $config['url_base_endpoints'] . $endpoint;
        $token = $config['token']; 

        if (\boctulus\WooTMHExpress\helpers\is_cli())
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
        Files::localDump(
            [
                'url' => $endpoint,
                'body' => $data
            ], 'req.txt'); /////////////

        $response = static::getClient($endpoint)
        ->setBody($data)
        ->post()
        ->getResponse();

        Files::localDump($response, 'res.txt');  ///////////

        return $response;
    }

    static function get($endpoint)
    {
        $response = static::getClient($endpoint)
        ->get()
        ->getResponse()
        ;

        return $response;
    }

    /*
        Ej:

        $dest_address

        'Carlos B Zetina 138, Escandón I Seccion, Miguel Hidalgo, CDMX'

        $customer_data 

		array (
			'number_identification' => 123456,
			'full_name' => 'John Smith',
			'phone' => '5512213456',
			'email' => 'john.smith@ivoy.mx',
		)
		
        $package_data 

		array (
			'dimensions' =>
                array (
                    'volume' => 1,
                    'pieces' => 1,
                    'weight' => 1,
                ),
			'containt' => 'Prueba',
			'type_product' => 'Documentos',
		),
    */
    static function registrarEnvio($dest_address, $customer_data, $package_data){
        $cfg = \boctulus\WooTMHExpress\helpers\config();

        $geo_ay = Maps::getCoord($dest_address);

        // podria fallar si la direccion tiene un problema

        return static::registrar([
            'origin' =>
            array (
                'address'   => $cfg['origin']['address'],
                'latitude'  => $cfg['origin']['latitude'],
                'longitude' => $cfg['origin']['longitude'],
            ),
    
            /*
                Leer de la Orden y usar lib de geolocalizacion +++++++++++++
            */
    
            'destination' =>
            array (
                'address'   => $dest_address,
                'latitude'  => $geo_ay['lat'] ?? "0",
                'longitude' => $geo_ay['lon'] ?? "0",
            ),
    
            /*
                Leer de la Orden
            */
    
            'contact' => $customer_data,
            'package' => $package_data,
        ],$cfg['endpoints']['create_order']);
    }

    static function getPostalCodes(){
        $zip_codes = get_transient('tmh_allowed_zip_codes');

        if (!empty($zip_codes)){         
            return $zip_codes;
        }

        $config = \boctulus\WooTMHExpress\helpers\config();

        $res = static::get('codePostal');
        
        if ($res['http_code'] != 200){
            $error = "Error al obtener codigos postales via endpoint. Code: {$res['http_code']}.";

            if (!empty($res['errors'])){
                $error .= $res['errors'];
            }

            Files::localLogger($error);

            return false;
        }

        if (empty($res['data'])){
            Files::localLogger("No se encontraron codigos postales via endpoint");
            return false;
        }

        $zip_codes = array_column($res['data'], 'code'); 
        set_transient('tmh_allowed_zip_codes', $zip_codes, $config['allowed_zip_codes_expiration_time']);

        
        return $zip_codes;
    }

}