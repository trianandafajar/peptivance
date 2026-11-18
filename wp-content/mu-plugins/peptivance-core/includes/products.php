<?php

defined( 'ABSPATH' ) || exit;

function pvc_product_fields() {
	return array(
		'_pv_purity' => array(
			'label'       => 'Purity (%)',
			'placeholder' => '99.5',
		),
		'_pv_size'   => array(
			'label'       => 'Vial size',
			'placeholder' => '10mg',
		),
		'_pv_batch'  => array(
			'label'       => 'Batch number',
			'placeholder' => 'PV-2026-001',
		),
		'_pv_coa'    => array(
			'label'       => 'COA report URL',
			'placeholder' => 'https://',
		),
	);
}

function pvc_render_product_fields() {
	echo '<div class="options_group">';
	foreach ( pvc_product_fields() as $key => $field ) {
		woocommerce_wp_text_input(
			array(
				'id'          => $key,
				'label'       => $field['label'],
				'placeholder' => $field['placeholder'],
			)
		);
	}
	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'pvc_render_product_fields' );

function pvc_save_product_fields( $post_id ) {
	foreach ( array_keys( pvc_product_fields() ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$raw   = wp_unslash( $_POST[ $key ] );
			$value = '_pv_coa' === $key ? esc_url_raw( $raw ) : sanitize_text_field( $raw );
			update_post_meta( $post_id, $key, $value );
		}
	}
}
add_action( 'woocommerce_process_product_meta', 'pvc_save_product_fields' );

function pvc_single_product_facts() {
	global $product;
	if ( ! $product ) {
		return;
	}
	$rows = array(
		'Purity (HPLC)' => get_post_meta( $product->get_id(), '_pv_purity', true ),
		'Vial size'     => get_post_meta( $product->get_id(), '_pv_size', true ),
		'Batch'         => get_post_meta( $product->get_id(), '_pv_batch', true ),
	);
	$coa  = get_post_meta( $product->get_id(), '_pv_coa', true );
	echo '<ul class="pv-facts">';
	foreach ( $rows as $label => $value ) {
		if ( '' === $value ) {
			continue;
		}
		if ( 'Purity (HPLC)' === $label ) {
			$value .= '%';
		}
		echo '<li><span>' . esc_html( $label ) . '</span><strong>' . esc_html( $value ) . '</strong></li>';
	}
	echo '</ul>';
	if ( $coa ) {
		echo '<p class="pv-coa-link"><a href="' . esc_url( $coa ) . '" target="_blank" rel="noopener">View certificate of analysis</a></p>';
	}
	echo '<p class="pv-disclaimer">For laboratory research use only. Not for human or veterinary consumption.</p>';
}
add_action( 'woocommerce_single_product_summary', 'pvc_single_product_facts', 25 );

function pvc_remove_reviews_tab( $tabs ) {
	unset( $tabs['reviews'] );
	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'pvc_remove_reviews_tab', 98 );

add_filter( 'pre_option_woocommerce_enable_reviews', '__return_zero' );
add_filter( 'pre_option_woocommerce_enable_review_rating', '__return_zero' );

function pvc_product_in_cart( $product_id ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}
	return WC()->cart->find_product_in_cart( WC()->cart->generate_cart_id( $product_id ) ) ? true : false;
}

function pvc_loop_add_to_cart_link( $html, $product, $args ) {
	if ( ! $product->is_type( 'simple' ) || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return $html;
	}
	$in_cart = pvc_product_in_cart( $product->get_id() );
	return sprintf(
		'<a href="%s" data-quantity="1" class="button product_type_simple add_to_cart_button pv-loop-add%s" data-product_id="%d" data-product_sku="%s" data-add-url="%s" data-name="%s" aria-label="%s" rel="nofollow">%s</a>',
		esc_url( $in_cart ? wc_get_cart_url() : $product->add_to_cart_url() ),
		$in_cart ? ' is-in-cart' : ' ajax_add_to_cart',
		(int) $product->get_id(),
		esc_attr( $product->get_sku() ),
		esc_url( $product->add_to_cart_url() ),
		esc_attr( $product->get_name() ),
		esc_attr( ( $in_cart ? 'View cart for ' : 'Add to cart: ' ) . $product->get_name() ),
		esc_html( $in_cart ? 'View cart' : 'Add to cart' )
	);
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'pvc_loop_add_to_cart_link', 20, 3 );

function pvc_buy_now_button() {
	global $product;
	if ( ! $product || ! $product->is_type( 'simple' ) ) {
		return;
	}
	printf(
		'<button type="submit" name="add-to-cart" value="%d" formaction="%s" class="button pv-buy-now">Buy now</button>',
		(int) $product->get_id(),
		esc_url( add_query_arg( 'pv_buy_now', '1', get_permalink( $product->get_id() ) ) )
	);
}
add_action( 'woocommerce_after_add_to_cart_button', 'pvc_buy_now_button' );

function pvc_buy_now_redirect( $url ) {
	if ( isset( $_REQUEST['pv_buy_now'] ) ) {
		wc_clear_notices();
		return wc_get_checkout_url();
	}
	return $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'pvc_buy_now_redirect' );
