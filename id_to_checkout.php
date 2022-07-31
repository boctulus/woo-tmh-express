<?php

#Adding a Custom Special Field
#To add a custom field is similar. Let’s add a new field to checkout, after the order notes, by hooking into the following:

/**
 * Check if WooCommerce is active
 */

 if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

// if (is_checkout()) {
// 	return;
// }


/**
 * Add the field to the checkout
 */

add_action( 'woocommerce_after_order_notes', 'add_id_num_field' );

function add_id_num_field($checkout)
{
    $config = \boctulus\WooTMHExpress\helpers\config();

	if (session_id() == ''){
		session_start();
	}

	//$old_val = $checkout->get_value( 'id_num' );
	$old_val = isset($_SESSION['checkout_id_num_field']) ? $_SESSION['checkout_id_num_field'] : '';

    echo '<div id="id_num" placeholder="'. $config['input_placeholder'] .'">';

	if (!$config['input_visibility']){
		echo "<input type=\"hidden\" class=\"input-text\" name=\"id_num\" id=\"id_num\" value=\"$old_val\" />";
	} else {		
    	woocommerce_form_field( 'id_num', array(
        'type'          => 'text',
        'class'         => array('my-field-class form-row-wide'),
        'label'         => __('ID'),
        'placeholder'   => __($config['input_placeholder']),
		'required'		=> $config['input_required']
        ), $old_val);
	}
	
    echo '</div>';


	$cart_items = [];
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$cart_items[] = $cart_item['product_id'];
	}  

	?>
		<script>
			var cart_items = <?php echo json_encode($cart_items) ?>;
		</script>
	<?php
}

#Next we need to validate the field when the checkout form is posted. For this example the field is required and not optional:

/**
 * Process the checkout
 */
add_action('woocommerce_checkout_process', 'id_process');

function id_process() {
    $config = \boctulus\WooTMHExpress\helpers\config();

    // Check if set, if its not set add an error.
    if ($config['input_required'] && !$_POST['id_num'] ){
		wc_add_notice( __($config['text_input_required']), 'error' );
		return;
	}

	if (session_id() == ''){
		session_start();
	}

	$_SESSION['checkout_id_num_field'] = $_POST['id_num'];
}


#Finally, let’s save the new field to order custom fields using the following code:

/**
 * Update the order meta with field value
 */

add_action( 'woocommerce_checkout_update_order_meta', 'id_num_update_order_meta' );

function id_num_update_order_meta( $order_id ) {	
    if (isset($_POST['id_num']) && !empty( $_POST['id_num'] ) ) {
        update_post_meta( $order_id, 'id_num', sanitize_text_field( $_POST['id_num'] ) );
    } 
}