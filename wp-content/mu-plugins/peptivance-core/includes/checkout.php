<?php

defined( 'ABSPATH' ) || exit;

function pvc_checkout_fields( $fields ) {
	unset( $fields['billing']['billing_company'], $fields['shipping']['shipping_company'] );
	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['required']    = true;
		$fields['billing']['billing_phone']['placeholder'] = 'Phone number for the courier';
	}
	foreach ( array( 'billing', 'shipping' ) as $group ) {
		if ( isset( $fields[ $group ][ $group . '_address_2' ] ) ) {
			$fields[ $group ][ $group . '_address_2' ]['label']       = 'Apartment, suite, unit';
			$fields[ $group ][ $group . '_address_2' ]['placeholder'] = '';
			$fields[ $group ][ $group . '_address_2' ]['label_class'] = array();
		}
	}
	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments']['label']       = 'Delivery notes';
		$fields['order']['order_comments']['placeholder'] = 'Anything the courier should know';
	}
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'pvc_checkout_fields' );

function pvc_terms_text() {
	return 'I confirm these products are for laboratory research use only, and I agree to the [terms].';
}
add_filter( 'woocommerce_get_terms_and_conditions_checkbox_text', 'pvc_terms_text' );

function pvc_free_shipping_notice() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	$threshold = 75;
	$subtotal  = (float) WC()->cart->get_subtotal();
	if ( $subtotal <= 0 ) {
		return;
	}
	if ( $subtotal >= $threshold ) {
		echo '<div class="pv-ship-note is-reached"><strong>Free UK shipping unlocked.</strong> Your order qualifies for free tracked delivery in the UK.</div>';
		return;
	}
	$left = $threshold - $subtotal;
	$pct  = max( 4, min( 100, round( $subtotal / $threshold * 100 ) ) );
	echo '<div class="pv-ship-note"><p>Add <strong>' . wp_kses_post( wc_price( $left ) ) . '</strong> more for free UK tracked shipping.</p><span class="pv-ship-note__bar"><i style="width:' . (int) $pct . '%"></i></span></div>';
}
add_action( 'woocommerce_before_cart', 'pvc_free_shipping_notice', 5 );

function pvc_order_received_text( $text ) {
	return 'Thank you. Your order has been received and is being prepared. A confirmation will be sent to your email address.';
}
add_filter( 'woocommerce_thankyou_order_received_text', 'pvc_order_received_text' );

function pvc_place_order_text() {
	return 'Place order';
}
add_filter( 'woocommerce_order_button_text', 'pvc_place_order_text' );

function pvc_hide_flat_when_free( $rates ) {
	$free = array();
	foreach ( $rates as $id => $rate ) {
		if ( 'free_shipping' === $rate->get_method_id() ) {
			$free[ $id ] = $rate;
		}
	}
	return $free ? $free : $rates;
}
add_filter( 'woocommerce_package_rates', 'pvc_hide_flat_when_free', 100 );

function pvc_ajax_cart_qty() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'pvc_cart' ) ) {
		wp_send_json_error( array( 'message' => 'Your session expired. Please reload the page.' ), 403 );
	}
	$key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
	$qty = isset( $_POST['qty'] ) ? max( 1, (int) $_POST['qty'] ) : 1;
	$cart = WC()->cart;
	if ( ! $cart || ! $key ) {
		wp_send_json_error( array( 'message' => 'The cart could not be loaded.' ), 400 );
	}
	$item = $cart->get_cart_item( $key );
	if ( ! $item ) {
		wp_send_json_error( array( 'message' => 'That item is no longer in your cart.' ), 404 );
	}
	$product = $item['data'];
	$message = '';
	$max     = $product->get_max_purchase_quantity();
	if ( $max > 0 && $qty > $max ) {
		$qty     = $max;
		$message = sprintf( 'Only %d available for this product.', $max );
	}
	if ( $qty > 99 ) {
		$qty     = 99;
		$message = 'The maximum per order is 99.';
	}
	$cart->set_quantity( $key, $qty, true );
	$cart->calculate_totals();
	wp_send_json_success(
		array(
			'qty'     => $qty,
			'message' => $message,
		)
	);
}
add_action( 'wc_ajax_pvc_cart_qty', 'pvc_ajax_cart_qty' );

function pvc_cart_qty_args( $args, $product ) {
	if ( is_cart() ) {
		$args['min_value'] = 1;
	}
	return $args;
}
add_filter( 'woocommerce_quantity_input_args', 'pvc_cart_qty_args', 20, 2 );

add_filter( 'pre_option_woocommerce_enable_coupons', function () {
	return 'no';
} );
