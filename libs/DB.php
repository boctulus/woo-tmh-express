<?php

namespace boctulus\WooTMHExpress\libs;

use boctulus\WooTMHExpress\libs\Sinergia;

/*
    Especifico de Sinergia
*/
class DB {
    static function getOrders($status){
        global $wpdb;

        $sql = "SELECT * FROM `{$wpdb->prefix}sinergia_queue` WHERE `status` = '$status'";
        $res = $wpdb->get_results($sql, ARRAY_A);

        return $res;
    }

    static function getOrderIds($status){
        global $wpdb;

        $sql = "SELECT * FROM `{$wpdb->prefix}sinergia_queue` WHERE `status` = '$status'";
        $res = $wpdb->get_results($sql, ARRAY_A);

        $res = array_column($res, 'order_id');

        return $res;
    }

    static function getComprobanteByOrderId($order_id, $tipo){
        global $wpdb, $config;
        
        Sinergia::validTypeOrFail($tipo);

        $subfix = ($tipo == Sinergia::BOLETA) ? 'boletas' : 'facturas';

        $sql = "SELECT * FROM `{$wpdb->prefix}sinergia_$subfix` WHERE `order_id` = '$order_id' LIMIT 1";
        $res = $wpdb->get_results($sql, ARRAY_A);

        if (empty($res)){
            return;
        }

        $row = $res[0];

        $serie = $row['serie'];
        $letra = substr($serie, 0, 1);

        $num   = (string) $row['correlativo'];
        $ceros = str_repeat('0', strlen($config['consecutivo_facturas']) - 1 - strlen($num) ); 
        
        $comprobante = $letra . $ceros . $num; 

        return $comprobante;
    }

    static function orderEnqueue($id, $status = null){
        global $wpdb;
        
        /*
            Preventivamente, borro por si existiera
        */
        $sql = "DELETE FROM `{$wpdb->prefix}sinergia_queue` WHERE order_id = $id";
        $ok  = $wpdb->query($sql);

        $wpdb->insert($wpdb->prefix . 'sinergia_queue', array(
            'order_id' => $id,
            'status'   => $status
        ));
    }

    // 
    static function orderDequeue($status){
        global $wpdb;

        $sql = "SELECT * FROM `{$wpdb->prefix}sinergia_queue` WHERE `status` = '$status' ORDER BY id DESC LIMIT 1";
        $row = $wpdb->get_row($sql, ARRAY_A);

        $id  = $row['id'];

        if ($id === null){
            return false;
        }

        $sql = "DELETE FROM `{$wpdb->prefix}sinergia_queue` WHERE id = $id";
        $ok  = $wpdb->query($sql);

        return $row;
    }

    static function orderExists($order_id){
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}sinergia_queue` WHERE order_id = $order_id";
        $res = $wpdb->get_var($sql);

        return (((int) $res) > 0);
    }

    static function orderDelete($order_id, $status = null){
        global $wpdb;

        $and_status = ($status != null) ? "AND status = '$status'" : '';
        
        $sql = "DELETE FROM `{$wpdb->prefix}sinergia_queue` WHERE order_id = $order_id $and_status";
        $aff = $wpdb->query($sql);

        return (!empty($aff));
    }
}

