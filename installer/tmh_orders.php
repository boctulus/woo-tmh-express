<?php

# INSTALLER

global $wpdb;

$table_name      = "tmh_orders";
$table_version   = '1.0.0';
$charset_collate = $wpdb->get_charset_collate();


$table_name = $wpdb->prefix . $table_name;

if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name ) {

    $sql = "CREATE TABLE `$table_name` ( 
        `id` INT (11) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
        `woo_order_id` INT (11) NOT NULL, 
        `tmh_order_id` INT (11) DEFAULT NULL, 
        `tracking_num` INT (11) DEFAULT NULL, 
        `try_count`    INT (11) DEFAULT 0, 
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) 
    ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $ok = dbDelta($sql);

    if (!$ok){
        return;
    }

    // ALTER TABLE `wp_sinergia_queue` ADD UNIQUE(`order_id`);
    $sql = "ALTER TABLE `$table_name` ADD UNIQUE(`order_id`);";

    $ok = dbDelta($sql);

    if (!$ok){
        return;
    }

    add_option($table_name, $table_version);
}


