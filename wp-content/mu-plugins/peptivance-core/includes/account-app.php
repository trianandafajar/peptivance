<?php

defined( 'ABSPATH' ) || exit;

function pvc_app_active() {
	return function_exists( 'is_account_page' ) && is_account_page() && is_user_logged_in();
}

function pvc_app_page_active() {
	return is_user_logged_in() && function_exists( 'is_cart' ) && ( is_cart() || is_checkout() );
}

function pvc_app_any() {
	return pvc_app_active() || pvc_app_page_active();
}

function pvc_app_icon( $key ) {
	$paths = array(
		'dashboard'       => '<rect x="3.5" y="3.5" width="7" height="7" rx="1"/><rect x="13.5" y="3.5" width="7" height="7" rx="1"/><rect x="3.5" y="13.5" width="7" height="7" rx="1"/><rect x="13.5" y="13.5" width="7" height="7" rx="1"/>',
		'orders'          => '<path d="M4 7.5l8-4 8 4v9l-8 4-8-4z"/><path d="M4 7.5l8 4 8-4M12 11.5v9"/>',
		'edit-address'    => '<path d="M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.4"/>',
		'edit-account'    => '<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5c0-4 3.4-6.5 7.5-6.5s7.5 2.5 7.5 6.5"/>',
		'customer-logout' => '<path d="M9 4.5H5.5v15H9M15 8l4 4-4 4M19 12H9.5"/>',
		'checkout'        => '<rect x="3.5" y="5.5" width="17" height="13" rx="1.5"/><path d="M3.5 10h17M7 15h3"/>',
		'cart'            => '<path d="M3 4h2.2l2 11h10.2l2-8H6.4"/><circle cx="9.5" cy="19.5" r="1.4"/><circle cx="16.5" cy="19.5" r="1.4"/>',
		'menu'            => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'shop'            => '<path d="M4 9l1.5-4.5h13L20 9M4 9v10.5h16V9M4 9h16M9.5 19.5v-5h5v5"/>',
		'close'           => '<path d="M6 6l12 12M18 6L6 18"/>',
	);
	$p = isset( $paths[ $key ] ) ? $paths[ $key ] : '';
	return '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';
}

function pvc_orders_status_cell( $order ) {
	echo pvc_status_label( $order ); // phpcs:ignore WordPress.Security.EscapeOutput
}
add_action( 'woocommerce_my_account_my_orders_column_order-status', 'pvc_orders_status_cell' );

function pvc_orders_filters() {
	$orders = wc_get_orders(
		array(
			'customer_id' => get_current_user_id(),
			'limit'       => -1,
			'return'      => 'objects',
		)
	);
	if ( count( $orders ) < 2 ) {
		return;
	}
	$counts = array();
	foreach ( $orders as $o ) {
		$s            = $o->get_status();
		$counts[ $s ] = isset( $counts[ $s ] ) ? $counts[ $s ] + 1 : 1;
	}
	echo '<div class="pv-filter" role="tablist" aria-label="Filter orders by status">';
	echo '<button type="button" class="pv-filter__chip is-active" data-pv-filter="all" role="tab" aria-selected="true">All <span>' . count( $orders ) . '</span></button>';
	foreach ( $counts as $status => $n ) {
		echo '<button type="button" class="pv-filter__chip" data-pv-filter="' . esc_attr( $status ) . '" role="tab" aria-selected="false">' . esc_html( wc_get_order_status_name( $status ) ) . ' <span>' . (int) $n . '</span></button>';
	}
	echo '</div>';
}
add_action( 'woocommerce_before_account_orders', 'pvc_orders_filters' );

function pvc_view_order_actions( $order ) {
	if ( ! $order || (int) $order->get_user_id() !== get_current_user_id() ) {
		return;
	}
	echo '<div class="pv-order-actions">';
	echo '<a class="pv-btn pv-btn--ghost" href="' . esc_url( wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) ) ) . '">Back to orders</a>';
	echo '</div>';
}
remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );
add_action( 'woocommerce_order_details_after_order_table', 'pvc_view_order_actions', 20 );

function pvc_app_template( $template ) {
	if ( pvc_app_any() ) {
		return PVC_DIR . 'templates/account-app.php';
	}
	return $template;
}
add_filter( 'template_include', 'pvc_app_template', 99 );

function pvc_app_body_class( $classes ) {
	if ( pvc_app_any() ) {
		$classes[] = 'pv-app';
	}
	return $classes;
}
add_filter( 'body_class', 'pvc_app_body_class' );

function pvc_app_title() {
	global $wp;
	if ( pvc_app_page_active() ) {
		if ( is_cart() ) {
			return array( 'cart', 'Your cart' );
		}
		if ( is_wc_endpoint_url( 'order-received' ) ) {
			return array( 'checkout', 'Order confirmed' );
		}
		return array( 'checkout', 'Checkout' );
	}
	$titles = array(
		'dashboard'     => 'Overview',
		'orders'        => 'Orders',
		'view-order'    => 'Order details',
		'edit-address'  => 'Addresses',
		'edit-account'  => 'Account details',
		'lost-password' => 'Password',
	);
	$current = 'dashboard';
	foreach ( array_keys( $titles ) as $key ) {
		if ( 'dashboard' !== $key && isset( $wp->query_vars[ $key ] ) ) {
			$current = $key;
			break;
		}
	}
	$title = $titles[ $current ];
	if ( 'view-order' === $current ) {
		$order = wc_get_order( absint( $wp->query_vars['view-order'] ) );
		if ( $order ) {
			$title = 'Order #' . $order->get_order_number();
		}
	}
	return array( $current, $title );
}

function pvc_app_enqueue() {
	if ( ! pvc_app_any() ) {
		return;
	}
	$css = PVC_DIR . 'assets/css/account-app.css';
	wp_enqueue_style( 'pvc-account-app', PVC_URL . 'assets/css/account-app.css', array( 'pvc-site' ), file_exists( $css ) ? filemtime( $css ) : PVC_VERSION );
	$js = PVC_DIR . 'assets/js/account-app.js';
	wp_enqueue_script( 'pvc-account-app', PVC_URL . 'assets/js/account-app.js', array(), file_exists( $js ) ? filemtime( $js ) : PVC_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'pvc_app_enqueue', 30 );
