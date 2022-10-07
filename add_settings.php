<?php

function get_opt(string $name){
    $config = __DIR__ . '/config/config.php';

    return get_option($name) ?? $config[$name] ?? null;
}

function update_empty_option($name, $val){    
    if (get_option($name) == null){
        update_option($name, $val);
    }
}

$config = include __DIR__ . '/config/config.php';


update_empty_option('tmh_shipping_cost', $config['tmh_shipping_cost']);
update_empty_option('tmh_shipping_calculation', $config['shipping_calculation']);
update_empty_option('tmh_token', $config['token']);
update_empty_option('tmh_order_status_trigger', $config['order_status_trigger']);
update_empty_option('tmh_order_status_error', $config['order_status_error']);


function custom_plugin_register_settings() {
    register_setting('custom_plugin_options_group', 'tmh_shipping_cost');
    register_setting('custom_plugin_options_group', 'tmh_shipping_calculation');
    register_setting('custom_plugin_options_group', 'tmh_token');
    register_setting('custom_plugin_options_group', 'tmh_order_status_trigger');
    register_setting('custom_plugin_options_group', 'tmh_order_status_error');
}

add_action('admin_init', 'custom_plugin_register_settings');

function custom_plugin_setting_page() {

// add_options_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '' )
add_options_page('Custom Plugin', 'Config. TMH Express', 'manage_options', 'custom-plugin-setting-url', 'tmh_express_page_html_form');
// tmh_express_page_html_form is the function in which I have written the HTML for my custom plugin form.
}

add_action('admin_menu', 'custom_plugin_setting_page');

function tmh_express_page_html_form() { ?>
<div class="wrap">
    <h2>Configuración de TMH Express</h2>
    <form method="post" action="options.php">
        <?php settings_fields('custom_plugin_options_group'); ?>

        <table class="form-table">
            <tr>
                <th><label for="tmh_shipping_cost">Costo de envio:</label></th>
                <td>
                    <input type = 'text' class="regular-text" id="tmh_shipping_cost" name="tmh_shipping_cost" value="<?php echo get_option('tmh_shipping_cost'); ?>">
                </td>
            </tr>

            <tr>
                <th><label for="tmh_shipping_calculation">Costo por órden o ítem:</label></th>
                <td>
                    <input type = 'text' class="regular-text" id="tmh_shipping_calculation" name="tmh_shipping_calculation" value="<?php echo get_option('tmh_shipping_calculation'); ?>">
                </td>
            </tr>

            <tr>
                <th><label for="tmh_token">Token:</label></th>
                <td>
                    <input type = 'text' class="regular-text" id="tmh_token" name="tmh_token" value="<?php echo get_option('tmh_token'); ?>">
                </td>
            </tr>

            <tr>
                <th><label for="tmh_order_status_trigger">Estado de órden que dispara la comunicación con TMH:</label></th>
                <td>
                    <input type = 'text' class="regular-text" id="tmh_order_status_trigger" name="tmh_order_status_trigger" value="<?php echo get_option('tmh_order_status_trigger'); ?>">
                </td>
            </tr>

            <tr>
                <th><label for="tmh_order_status_error">Estado de órden en caso de fallo:</label></th>
                <td>
                    <input type = 'text' class="regular-text" id="tmh_order_status_error" name="tmh_order_status_error" value="<?php echo get_option('tmh_order_status_error'); ?>">
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
</div>
<?php } ?>