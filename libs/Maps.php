<?php

/*
    @author  Pablo Bozzolo boctulus@gmail.com
*/

namespace boctulus\WooTMHExpress\libs;

use boctulus\WooTMHExpress\libs\Strings;
use boctulus\WooTMHExpress\libs\Arrays;
use boctulus\WooTMHExpress\libs\Url;
use boctulus\WooTMHExpress\libs\ApiClient;

/*
    @author Pablo Bozzolo

    Ofuscar !
*/
class Maps
{
    /*
        La direccon que debe pasarse es por ejemplo:
        
        'Diego de Torres 5, Acala de Henaes, Madrid'
    */
    static function getCoord(string $address)
    {   
        $apiKey = 'AIzaSyAJI6R4DUNCfwvQYZJZGltf9qztLnQMzKY'; // hardcoded

        // Get JSON results from this request

        $client = new ApiClient('https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($address).'&sensor=false&key='.$apiKey);

        $geo_res = $client
        ->disableSSL()
        ->get();

        if ($geo_res->getStatus() != 200){
            return null;
        }

        $geo = $geo_res->getResponse()['data'];

        if (isset($geo['status']) && ($geo['status'] == 'OK')) {
            $lat = $geo['results'][0]['geometry']['location']['lat']; // Latitude
            $lon = $geo['results'][0]['geometry']['location']['lng']; // Longitude

            return [
                'lat' => $lat,
                'lon' => $lon
            ];
        }
    }

}
   