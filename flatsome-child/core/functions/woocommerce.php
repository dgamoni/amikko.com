<?php 


/**
 * Auto Complete all WooCommerce orders.
 */

add_action( 'woocommerce_payment_complete', 'my_change_status_function' );
add_action( 'woocommerce_thankyou', 'my_change_status_function', 20, 1 );

    function my_change_status_function( $order_id ) {

        $order = new WC_Order( $order_id );
        $order_pay_method = get_post_meta( $order->id, '_payment_method', true );
        //var_dump($order_pay_method);

        if ( $order_pay_method == 'easypay_mbway_2' || $order_pay_method == 'easypay_mb_2' ){
        	$order->update_status( 'processing', __( 'Payment received.', 'wc-gateway-offline' ) );
        }
    }

//add_action( 'woocommerce_thankyou', 'custom_woocommerce_auto_complete_order' );
function custom_woocommerce_auto_complete_order( $order_id ) { 
    if ( ! $order_id ) {
        return;
    }

	$payment_method = get_post_meta( $order_id, '_payment_method', true );
	var_dump($payment_method);

    $order = wc_get_order( $order_id );
    //$order->update_status( 'completed' );


    // foreach ( $order->get_items() as $item_id => $main_order_item ) {
    // 	//var_dump($main_order_item);
    // }


} 