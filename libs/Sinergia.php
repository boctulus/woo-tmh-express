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

class Sinergia 
{  
    const BOLETA  = 'boletas';
    const FACTURA = 'facturas';

    static function getToken(){
        return get_transient('sinergia_token');
    }

    /*
        Loguearse antes de expiracion o si una respuesta es

        array (
            'data' =>
            array (
                'status' => 'logout',
            ),
            'http_code' => 200,
            'errors' => '',
        )
    */
    static function login()
    {        
        global $config;

        $client = new ApiClient();

        $postfields = array();
        $postfields['_username'] = $config['username'];
        $postfields['_password'] = $config['password'];

        $client
        ->setRetries(3)
        ->setHeaders([
            'Content-Type' => 'multipart/form-data'
        ])
        ->setBody($postfields, false)
        ->disableSSL()
        ->request('https://devapi.sinergia.pe/login_check', 'POST');        

        if ($client->getStatus() != 200 || !empty($client->getErrors())){
            if (!empty($client->getErrors())){
                Files::dump($client->getErrors());
            }

            throw new \Exception("Auth falló");
        }

        $res = $client->getResponse(true);
        
        $token = $res['data']['authToken'];

        return $token;
    }

    /*
        Descompone en serie y consecutivo un string
        que puede ser un comprobante o una "interface" de Sinergia

        Dado F00100009447,

        F001 es la serie (los primeros 4 caracteres)
        9447 el consecutivo (sin ceros delante)
    */
    static function parseComprobante($str){
        $_ = strpos($str, '_');

        if ($_ !== false){
            $str = substr($str, $_ +1);
        }

        $serie = substr($str, 0, 4);
        $num   = (int) substr($str, 5);

        return [
            'serie' => $serie,
            'num'   => $num
        ];
    }

    /*
        Órden:

        {urlSinergia}}/interfaces/interfacesventa/homologarBienesServicios
        {{url}}/interfaces/interfacesventa/homologarCliente
        {{url}}/interfaces/interfacesventa/homologarModVenta
    */


    static function getClient($endpoint){
        global $config;

        $token = get_transient('sinergia_token');

        if (empty($token)){
            $token = static::login();

            if (empty($token)){
                throw new \Exception("Token no se pudo obtener");
            }

            set_transient('sinergia_token', $token, $config['token_expiration'] - 30); // 30 seg. antes
        }

        $ruta = $config['url_base_endpoints'] . $endpoint;
        
        if (is_cli())
            dd($ruta, 'ENDPOINT *****');

        $client = (new ApiClient($ruta));

        $client
        ->setHeaders(
            [
                "Content-type"  => "Application/json",
                "authToken" => "$token"
            ]
        )
        ->setRetries(3);

        //dd($token, 'TOKEN USADO');

        if ($config['dev_mode']){
            $client->disableSSL();
        }        
        
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

    
    /*        
        Nueva ruta para extraer PDF:
        https://devapi.sinergia.pe/aniruddha/invoice-20602903312-F001-254/pdf

        Formato (seg'un Jason)

        https://devapi.sinergia.pe/aniruddha/invoice-{B1_RUC}-{SERIE_EJ_F001}-{CORRELATIVO_SIN_CEROS}/pdf

        A ver...

        'B1_RUC'            => '20602903312',

        y

        array (
            'idInterface' => '01_F00100009439', <--------- comprobante
            'interface' => 'Empresas\\Clientes',
            'codigo' => '20538856674',
            'comentario' => 'no existe el nro documento del cliente',
        ),

        Ser;ia,...

        https://devapi.sinergia.pe/aniruddha/invoice-20602903312-F001-9439/pdf

    */
    static function getPdfUrl($comprobante){
        global $config;

        $_c = static::parseComprobante($comprobante);
        $serie       = $_c['serie'];
        $correlativo = $_c['num'];

        $ruc_prop = static::getRuCPropietario();
        $endpoint = "/aniruddha/invoice-{$ruc_prop}-$serie-$correlativo/pdf";

        return $config['url_base_endpoints'] . $endpoint;
    }

    static function getPdfUrlByOrderId($order_id){
        $ruc_cliente  = static::get_ruc_from_order($order_id);

		$tipo = empty($ruc_cliente) ? static::BOLETA : static::FACTURA;

        $comprobante = DB::getComprobanteByOrderId($order_id, $tipo);

        // Esto ya ser'ia un Error
        if (empty($comprobante)){
            return;
        }

        return static::getPdfUrl($comprobante); 
    }


    /*
        pending

        on-hold
        processing
        
        cancelled
        refunded

        failed
        completed

        pending > on-hold > processing > completed > refunded
        pending > processing
        pending > failed > cancelled
    */
    static function is_valid_order_transition($from, $to){
        $valid = [
            "on-hold>processing",   // ok
            "pending>processing",   // ok
            "processing>completed", // ok
            "on-hold>completed",    // ok (manualmente se podría dar el caso)
        ];

        return in_array($from .'>'. $to, $valid);
    }

    static function get_ruc_from_order($order_id){
        $meta_key = '_billing_wooccm12';
        return get_post_meta($order_id, $meta_key, true);
    }

    static function get_direccion_completa_empresa($order_id){
        $meta_key = '_billing_wooccm13';
        return get_post_meta($order_id, $meta_key, true);
    }

    static function get_direccion_alternativa($order_id){
        $meta_key_state = '_shipping_state';
        $meta_key_city  = '_shipping_city';
        $meta_key_addr1 = '_shipping_address_1';

        $depto = get_post_meta($order_id, $meta_key_state, true);

        if (empty($depto)){
            return null;
        }

        return [
            'State'    => $depto,
            'City'	   => get_post_meta($order_id, $meta_key_city, true),
            'Address Line 1'  => get_post_meta($order_id, $meta_key_addr1, true),
        ];
    }

    static function validTypeOrFail($tipo){
        if ($tipo !=  static::BOLETA && $tipo != static::FACTURA){
            throw new \InvalidArgumentException("El tipo de consecutivo es tipo incorrecto");
        }
    }

    static function getCorrelativo($tipo, $order_id = null, $increment = true){
        global $config, $wpdb;
    
        static::validTypeOrFail($tipo);
    
        if ($order_id === null && $increment === true){
            throw new \InvalidArgumentException("Valor inesperado para \$increment. El order_id solo puede estar vacio si increment es false");
        }

        $sql = "SELECT correlativo FROM `{$wpdb->prefix}sinergia_{$tipo}` ORDER BY `datetime` DESC LIMIT 1;";
        $res = $wpdb->get_col($sql);
        $last_id = $res[0] ?? null;  // '100009442'
    
        // SI es la primera boleta o factura
        if ($last_id == null){
            if ($increment){
                // me quedo con la parte num'erica
                $last_id = (int) substr($config["consecutivo_{$tipo}"], 1);
                $serie   = static::parseComprobante($config["consecutivo_{$tipo}"])['serie'];  // nuevo
            
                $sql = "INSERT INTO `{$wpdb->prefix}sinergia_{$tipo}` (`correlativo`, `serie`, `order_id`, `datetime`) VALUES ($last_id, '$serie'   , $order_id, CURRENT_TIMESTAMP);";

                //dd($sql);
                $wpdb->query($sql);
            }
    
            return $config["consecutivo_{$tipo}"];
        }         

        // SI ... ya existia boleta o factura previa
       
        /*
            --[ LETRA ]--
            'B'
        */
        $letra_consecutivo = substr($config["consecutivo_{$tipo}"], 0, 1);

        /*
            --[ LONG ]--
            11
        */
        $long = strlen($config["consecutivo_{$tipo}"]) -1;

        if ($increment){
            $serie = static::parseComprobante($config["consecutivo_{$tipo}"])['serie'];
            $sql   = "INSERT INTO `{$wpdb->prefix}sinergia_{$tipo}` (`correlativo`, `serie`, `order_id`, `datetime`) VALUES (NULL, '$serie'   , $order_id, CURRENT_TIMESTAMP);";

            $wpdb->query($sql);

            //dd($sql);
        
            $sql = "SELECT correlativo FROM `{$wpdb->prefix}sinergia_{$tipo}` ORDER BY `datetime` DESC LIMIT 1;";
            $res = $wpdb->get_col($sql);
            $last_id = 	((int) $res[0]) ?? null;

            //dd($res, 'RES correlativo');
        }

        return $letra_consecutivo . str_pad((string) $last_id, $long, '0', STR_PAD_LEFT);
    }

    // 31-may-2022
    static function getRuCPropietario(){
        global $config;

        return $config['dev_mode'] ? $config['demo_ruc'] : $config['B1_RUC'];
    }

    /*
        Proceso completo de registro de una venta
    */
    static function homologar($order_id){
        global $wpdb, $config;

        $is_dev	 = $config['dev_mode'];
        $ruc_api = $config['demo_ruc'] ?? null;

        // 31-may-2022
        $ruc_propietario = static::getRuCPropietario();

        if ($is_dev && $ruc_api === null){
            throw new \Exception("demo_ruc es necesario en desarrollo");
        }

        // Getting an instance of the order object
        $order  = wc_get_order( $order_id );

        if (empty($order)){
            throw new \Exception("Orden con id = $order_id invalida");
            //return;
        }

        if ($config['only_paid'] && !$order->is_paid()){
            return;
        }

        $order_items = Orders::getOrderItems($order);

        $ventas         = [];	
        $ventas_detalle = [];

        $total			 = 0;
        $total_descuento = 0;
        $total_peso	     = 0;

        // redondeo a máximo N decimales
        $round = function($num){
            $num = (float) $num;
            return round($num, 5);
        };

        //foo();//////////

        // $item es del tipo WC_Order_Item_Product
        foreach ( $order_items as $item_id => $item ) 
        {
            $woo_item = Orders::orderItemToArray($item);

            $sku = $woo_item['sku'];

            $descuento =  $woo_item['total_non_discounted'] - $woo_item['total_discounted'];
            
            $total_descuento += $descuento;
            $total           += (float) $woo_item['sale_price'];
            $total_peso		 += (float) $woo_item['weight'];	

            $precio_venta = $woo_item['price'];

            $detalle = [
                'F1_ITEM' 		=> NULL,
                'F2_UNIDAD'		=> 'NIU',   ///// re-cehquear
                'F3_CANTIDAD'	=> $woo_item['qty'],

                /*
                    INSTRUCCIÓN

                    "F4_CODIGO_PRODUCTO":10001,  Extraer del campo SKU del producto
                    "F5_CODIGO_SUNAT":"000",   SIEMPRE “000”
                */
                'F4_CODIGO_PRODUCTO' => $sku,
                'F5_CODIGO_SUNAT'    => '000',
                // F6 no existe 

                'F7_DESCRIPCION' 	=> $woo_item['product_name'],
                'F8_PRECIO' 		=> null,
                'F9_PRECIOVENTA' 	=> $precio_venta,
                'F10_TIPOPRECIO' 	=> '01',
                'F11_PRECIOGRATIS' 	=> 0,
                'F12_MONTOIGV' 		=> 18,

                /*
                    "F13_TOTAL":6,  Sinergia dice que este campo no está en funcionamiento, omitir, dejar en valor 0.
                */
                'F13_SUBTOTAL'  => 0, 

                'F14_TIPOAFECTA' => 10, // INSTRUCCIÓN DOC

                /*
                    "F15_CODIGOSIS":10001,  Extraer del campo SKU del producto
                */
                'F15_CODIGOSIS' => $sku,

                /*
                    F16_PORCENTAJE_DESCUENTO":0,  SIEMPRE 0
                "F17_BIENSERVICIO":"b",  SIEMPRE “b”
                */

                'F16_PORCENTAJE_DESCUENTO' => 0,
                'F17_BIENSERVICIO' => 'b',

                /*
                    "F18_IGV_TAX":true,  SIEMPRE true
                    "F18_IGV_AMOUNT":18,  SIEMPRE 18
                    "F19_ISC_TAX":false,  SIEMPRE false
                    "F19_ISC_AMOUNT":0  SIEMPRE 0
                */
                'F18_IGV_TAX'    => true,
                'F18_IGV_AMOUNT' => 18,
                'F19_ISC_TAX'    => false,
                'F19_ISC_AMOUNT' => 0
            ];
            
            $ventas_detalle[] = $detalle;

        } // end foreach items en órden


        $customer = Orders::getCustomerData($order);
        $billing  = $customer['billing'];

        $customer_full_name     = trim($billing['First Name'] . ' '. $billing['Last Name']);
        $customer_co            = trim($billing['Company']);

        $ruc_cliente            = Sinergia::get_ruc_from_order($order_id);
        $tipo_boleta_o_factura  = (!empty($ruc_cliente) && $ruc_cliente != '0' ) ? static::FACTURA : static::BOLETA;  // nuevo

        //dd($ruc_cliente, "RUC CLIENTE para ORDEN # $order_id");

        /*
            Instrucción en DOC
        */
        $dir_empresa = Sinergia::get_direccion_completa_empresa($order_id);
        $dir_alt     = Sinergia::get_direccion_alternativa($order_id);

        if (!empty($dir_alt)){
            $dir = "{$dir_alt['Address Line 1']}, {$dir_alt['City']}, {$dir_alt['State']}";

        }elseif (!empty($dir_empresa)){
            $dir = $dir_empresa;
        } else {
            $dir = "{$billing['Address Line 1']}, {$billing['City']}, {$billing['State']}";
        }

        if (!empty($dir_empresa)){
            $dir_legal = $dir_empresa;
        } else {
            $dir_legal = "{$billing['Address Line 1']}, {$billing['City']}, {$billing['State']}";
        }

        /*
            Armo el "JSON" unificado para Sinergia
        */

        $ventas = 
        [
            'A1_ID' 	=> null,  
            'A2_FECHAEMISION'  => Date::at('Y-m-d'),	
            'A3_HORAEMISION' => NULL,
            'A4_TIPODOCUMENTO' => (!empty($ruc_cliente)) ? '01' : '03', // INSTRUCCIÓN DOC		
            'A5_MONEDA' => 'USD',  // INSTRUCCIÓN DOC
            'A6_FECHAVENCIMIENTO' => NULL,
            'A7_DOCUMENTOREFERENCIA' => NULL,
            'A8_MOTIVONC' => NULL,
            'A9_FECHABAJA' => NULL,
            'A10_OBSERVACION' => NULL,
            'A11_TIPODOCUMENTOREFERENCIA' => NULL,
            'A12_WEIGHT' => (string) $total_peso, 

            'B1_RUC'    => $ruc_propietario,

            'D1_DOCUMENTO'			=> '', // abajo lo seteo
            'D2_TIPODOCUMENTO'		=> '', // abajo lo seteo
            'D3_DESCRIPCION' 		=> '', // abajo lo seteo

            // Departamento > Provincia > Distrito
            'D4_LEGAL_STREET' 		=> $dir_legal,
            'D4_LEGAL_DISTRICT' 	=> '',
            'D4_LEGAL_PROVINCE' 	=> '',
            'D4_LEGAL_STATE' 		=> '',
            'D4_UBIGEO'				=> null,
            
            'D5_DIRECCION' 			=> $dir,
            'D6_URBANIZACION'		=> null,

            /*
                INSTRUCCIÓN DOC

                [17:47, 30/4/2022] Jason Gonzales: Departamento es state y distrito es city
                [17:47, 30/4/2022] Jason Gonzales: Provincia dejalo vacío ""
            */
            'D7_PROVINCIA' 			=> '',
            'D8_DEPARTAMENTO' 		=> $billing['State'],
            'D9_DISTRITO'			=> $billing['City'],
            'D10_PAIS'				=> null,

            'D11_CORREO' 			=> $billing['Email'],
            'D12_CODIGO'			=> null,
            'D13_CODIGODIR'			=> '',			

            'G1_TOTALEXPORTA' => 0,
            'G2_TOTALGRAVADA' => 0,
            'G3_TOTALINAFECTA' => 0,
            'G4_TOTALEXONERADA' => 0,
            'G5_TOTAGRATUITA' => 0,
            'G6_TOTALDESCUENTOS' => $total_descuento,
            'G7_PORCENDETRA' => 0,
            'G8_TOTALDETRA' => 0,
            'G9_TOTALIGV' => 0,
            'G10_TOTALSUBTOTAL' => 0,
            'G13_TOTALGLOBALDESCU' => 0,
            'G14_TOTALVENTA' => $total,
            'G15_SUBTOTAL' => 0,

            /*
                INSTRUCCIÓN

                Actualización: por favor colocar en "H1_CODALMACEN" siempre null

                Actualizacion #2:

                Porque en tu TXT de la factura el msje de error dice:

                'success' => false,
                    'msg' => 'No existe Inventario\\Almacen con id: 1',

                Y me dijeron que eso se arregla colocando 1 en  'H1_CODALMACEN'
            */
            'H1_CODALMACEN' => '1', 
            'H2_SUCURSAL'   => '',
        ];

        /*
            Instrucción en DOC
        */
        if (!empty($ruc_cliente)){
            // FACTURA

            // Lógica de cambio de SIN DOCUMENTO a RUC
            $ventas['D1_DOCUMENTO']     = $ruc_cliente;
            $ventas['D2_TIPODOCUMENTO'] = '6';		
            
            // Razón social (billing phone)
            $billing_phone = $billing['Phone'];
            $ventas['D3_DESCRIPCION']  = "$customer_co ($billing_phone)";
        } else {
            // BOLETA 

            /*
                Para boletas en D1_DOCUMENTO colocar "0", dice "1234567890" o algo asi 
                y eso genera el error, enviemos así la trama pls
            */
            $ventas['D1_DOCUMENTO'] = '0';

            /*
                D2_TIPODOCUMENTO debe ser 1 
                estamos en boletas
            */
            $ventas['D2_TIPODOCUMENTO'] = '1';
            $ventas['D3_DESCRIPCION']  = $customer_full_name;
        }

        try {
            $wpdb->query('START TRANSACTION');
            
            /*
                Correlativo autoincremental
            */

            $inc_consecutivo = $config['consecutivo_mover' ];

            // Acá ocurre un INSERT INTO
            $ventas['A1_ID']  = $inc_consecutivo ? static::getCorrelativo($tipo_boleta_o_factura, $order_id) : $config['consecutivo_' . $tipo_boleta_o_factura];	

            // debug
            //dd($ventas['A1_ID'], 'A1_ID');

            $ventas['detalle'] = $ventas_detalle;

            $tabla_ventas = [
                $ventas		
            ];

            $data = [
                "ruc" => $ruc_propietario,
                "tabla_ventas" => $tabla_ventas
            ];

        
            // Request a Sinergia

            if (php_sapi_name() == 'cli')
                dd($data, 'DATA');

            $res = Sinergia::registrar($data, $config['endpoint_ruta_homologar_bienes_servicios']);
            if (php_sapi_name() == 'cli') 
                dd($res, 'RESPONSE REGISTRAR *BIEN O SERVICIO*');

            $exito = ($res['http_code'] == 200 && $res['data']['success'] == true);
            if ($exito){
                if (php_sapi_name() == 'cli')
                    dd('--- OK');
            } else {
                throw new \Exception('ERROR AL HOMOLOGAR *BIENES Y SERVICIOS' ." - Detalle: ". ($res['data']['msg'] ?? ''));
            }	

            $res = Sinergia::registrar($data, $config['endpoint_ruta_homologar_ciente']);
            if (php_sapi_name() == 'cli') 
                dd($res, 'RESPONSE REGISTRAR HOMOLOGAR *CLIENTE*');

            $exito = ($res['http_code'] == 200 && $res['data']['success'] == true);
            if ($exito){
                if (php_sapi_name() == 'cli')
                    dd('--- OK');
            } else {
                throw new \Exception('ERROR AL HOMOLOGAR *CLIENTE*' ." - Detalle: ". ($res['data']['msg'] ?? ''));
            }	

            $res = Sinergia::registrar($data, $config['endpoint_homologar_venta']);
            if (php_sapi_name() == 'cli') 
                 dd($res, 'RESPONSE REGISTRAR HOMOLOGAR *VENTA*');


            #foo(); //

            $exito = ($res['http_code'] == 200 && $res['data']['success'] == true);
            if ($exito){
                if (php_sapi_name() == 'cli')
                    dd('--- OK');
            } else {
                $detalle = $res['data']['msg'] ?? '';
                throw new \Exception('ERROR AL HOMOLOGAR *VENTA*' ." - Detalle: ". ($res['data']['msg'] ?? ''));
            }
            
            if (php_sapi_name() == 'cli')
                dd('---------------------- FIN ----------------------');

            $wpdb->query('COMMIT');
        } catch (\Exception $e) {
            if (php_sapi_name() == 'cli')
                dd($e->getMessage(), "Exception msg`");
            
            $wpdb->query('ROLLBACK');
            throw new \Exception($e->getMessage());
            //throw $e; 
        }
    }


}