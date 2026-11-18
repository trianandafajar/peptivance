<?php

defined( 'ABSPATH' ) || exit;

function pvc_account_menu( $items ) {
	unset( $items['downloads'] );
	$labels = array(
		'dashboard'       => 'Overview',
		'orders'          => 'Orders',
		'edit-address'    => 'Addresses',
		'edit-account'    => 'Account details',
		'customer-logout' => 'Log out',
	);
	$out = array();
	foreach ( $items as $key => $label ) {
		$out[ $key ] = isset( $labels[ $key ] ) ? $labels[ $key ] : $label;
	}
	return $out;
}
add_filter( 'woocommerce_account_menu_items', 'pvc_account_menu', 20 );

function pvc_account_body_class( $classes ) {
	if ( function_exists( 'is_account_page' ) && is_account_page() && is_user_logged_in() ) {
		$classes[] = 'pv-account-in';
		if ( ! is_wc_endpoint_url() ) {
			$classes[] = 'pv-account-dashboard';
		}
	}
	return $classes;
}
add_filter( 'body_class', 'pvc_account_body_class' );

function pvc_account_user_card() {
	$user = wp_get_current_user();
	echo '<div class="pv-account-card">';
	echo '<span class="pv-account-card__avatar">' . pvc_avatar_html( $user ) . '</span>';
	echo '<span class="pv-account-card__text"><strong>' . esc_html( $user->display_name ) . '</strong><span>' . esc_html( $user->user_email ) . '</span></span>';
	echo '</div>';
}
add_action( 'woocommerce_before_account_navigation', 'pvc_account_user_card' );

function pvc_status_label( $order ) {
	return '<span class="pv-status pv-status--' . esc_attr( $order->get_status() ) . '">' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</span>';
}

function pvc_account_dashboard() {
	$user   = wp_get_current_user();
	$first  = $user->first_name ? $user->first_name : $user->display_name;
	$orders = wc_get_orders(
		array(
			'customer_id' => $user->ID,
			'limit'       => -1,
			'return'      => 'objects',
		)
	);
	$open   = 0;
	foreach ( $orders as $o ) {
		if ( in_array( $o->get_status(), array( 'pending', 'processing', 'on-hold' ), true ) ) {
			$open++;
		}
	}
	$last    = $orders ? $orders[0] : null;
	$billing = get_user_meta( $user->ID, 'billing_address_1', true );
	$orders_url  = wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) );
	$address_url = wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) );
	$details_url = wc_get_endpoint_url( 'edit-account', '', wc_get_page_permalink( 'myaccount' ) );

	echo '<div class="pv-dash">';
	echo '<div class="pv-dash__hello"><h2>Welcome back, ' . esc_html( $first ) . '</h2><p>Track your orders, update your delivery details and manage your account from here.</p></div>';
	$spent = 0;
	foreach ( $orders as $o ) {
		if ( ! $o->has_status( array( 'failed', 'cancelled', 'refunded' ) ) ) {
			$spent += (float) $o->get_total();
		}
	}
	echo '<div class="pv-dash__stats">';
	echo '<a class="pv-stat" href="' . esc_url( $orders_url ) . '"><span class="pv-stat__value">' . count( $orders ) . '</span><span class="pv-stat__label">Total orders</span></a>';
	echo '<a class="pv-stat" href="' . esc_url( $orders_url ) . '"><span class="pv-stat__value">' . (int) $open . '</span><span class="pv-stat__label">Open orders</span></a>';
	echo '<a class="pv-stat" href="' . esc_url( $orders_url ) . '"><span class="pv-stat__value">' . wp_kses_post( wc_price( $spent ) ) . '</span><span class="pv-stat__label">Total spent</span></a>';
	echo '</div>';

	echo '<div class="pv-dash__block"><div class="pv-dash__head"><h3>Latest order</h3>';
	if ( $last ) {
		echo '<a href="' . esc_url( $last->get_view_order_url() ) . '">View order</a>';
	}
	echo '</div>';
	if ( $last ) {
		echo '<div class="pv-latest"><div><strong>Order #' . esc_html( $last->get_order_number() ) . '</strong><span>' . esc_html( wc_format_datetime( $last->get_date_created() ) ) . '</span></div><div>' . pvc_status_label( $last ) . '</div><div class="pv-latest__total">' . wp_kses_post( $last->get_formatted_order_total() ) . '</div></div>';
	} else {
		echo '<div class="pv-empty"><p>You have not placed an order yet.</p><a class="pv-btn pv-btn--solid" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">Browse research peptides</a></div>';
	}
	echo '</div>';

	echo '<div class="pv-dash__links">';
	echo '<a href="' . esc_url( $details_url ) . '"><strong>Account details</strong><span>Update your name, email, password and profile photo.</span></a>';
	echo '<a href="' . esc_url( $address_url ) . '"><strong>Addresses</strong><span>Edit your billing and delivery addresses.</span></a>';
	echo '<a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '"><strong>Continue shopping</strong><span>Explore the full research peptide catalogue.</span></a>';
	echo '</div>';
	echo '</div>';
}
add_action( 'woocommerce_account_dashboard', 'pvc_account_dashboard' );

function pvc_edit_account_avatar_field() {
	$user    = wp_get_current_user();
	$has_own = (bool) get_user_meta( $user->ID, 'pv_avatar_id', true );
	echo '<div class="pv-avatar-card" data-pv-avatar>';
	echo '<button class="pv-avatar-card__preview" type="button" data-pv-avatar-pick aria-label="Upload a new profile photo"><span class="pv-avatar-card__image" data-pv-avatar-image>' . pvc_avatar_html( $user ) . '</span><span class="pv-avatar-card__camera" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h3l1.5-2h7L17 8h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg></span></button>';
	echo '<div class="pv-avatar-card__body"><h3>Profile photo</h3><p>Shown in the header and on your account. Drop an image here or choose one, then adjust the crop.</p>';
	echo '<div class="pv-avatar-card__actions"><button class="pv-btn pv-btn--solid" type="button" data-pv-avatar-pick>Upload photo</button><button class="pv-btn pv-btn--ghost" type="button" data-pv-avatar-remove' . ( $has_own ? '' : ' hidden' ) . '>Remove</button></div>';
	echo '<p class="pv-avatar-card__msg" role="status" aria-live="polite" data-pv-avatar-msg></p>';
	echo '<input type="file" accept="image/jpeg,image/png,image/webp" hidden data-pv-avatar-file></div></div>';
}
add_action( 'woocommerce_edit_account_form_start', 'pvc_edit_account_avatar_field' );

function pvc_avatar_modal() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_user_logged_in() ) {
		return;
	}
	?>
	<div class="pv-modal" id="pv-avatar-modal" role="dialog" aria-modal="true" aria-labelledby="pv-avatar-title" hidden>
		<div class="pv-modal__backdrop" data-pv-avatar-cancel></div>
		<div class="pv-modal__panel pv-modal__panel--crop">
			<h2 class="pv-modal__title" id="pv-avatar-title">Adjust your photo</h2>
			<p class="pv-modal__text">Drag to reposition and use the slider to zoom.</p>
			<div class="pv-crop" data-pv-crop>
				<canvas class="pv-crop__canvas" width="320" height="320"></canvas>
				<span class="pv-crop__mask" aria-hidden="true"></span>
			</div>
			<label class="pv-crop__zoom"><span>Zoom</span><input type="range" min="1" max="3" step="0.01" value="1" data-pv-crop-zoom aria-label="Zoom"></label>
			<div class="pv-modal__actions">
				<button class="pv-btn pv-btn--ghost" type="button" data-pv-avatar-cancel>Cancel</button>
				<button class="pv-btn pv-btn--solid" type="button" data-pv-avatar-save>Save photo</button>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'pvc_avatar_modal', 6 );

function pvc_avatar_enqueue() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_user_logged_in() ) {
		return;
	}
	$js = PVC_DIR . 'assets/js/account.js';
	wp_enqueue_script( 'pvc-account', PVC_URL . 'assets/js/account.js', array(), file_exists( $js ) ? filemtime( $js ) : PVC_VERSION, true );
	wp_localize_script(
		'pvc-account',
		'pvAvatar',
		array(
			'api'   => esc_url_raw( rest_url( 'pvc/v1/avatar' ) ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'pvc_avatar_enqueue', 25 );

function pvc_avatar_upload( $request ) {
	$user  = wp_get_current_user();
	$files = $request->get_file_params();
	if ( empty( $files['avatar'] ) || UPLOAD_ERR_OK !== (int) $files['avatar']['error'] ) {
		return new WP_Error( 'pvc_avatar_missing', 'No image was received. Please try again.', array( 'status' => 400 ) );
	}
	$file = $files['avatar'];
	if ( (int) $file['size'] > 3 * MB_IN_BYTES ) {
		return new WP_Error( 'pvc_avatar_size', 'The photo must be 3 MB or smaller.', array( 'status' => 413 ) );
	}
	$check = wp_check_filetype_and_ext(
		$file['tmp_name'],
		$file['name'],
		array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'webp'         => 'image/webp',
		)
	);
	if ( empty( $check['type'] ) ) {
		return new WP_Error( 'pvc_avatar_type', 'The photo must be a JPG, PNG or WebP image.', array( 'status' => 415 ) );
	}
	$size = getimagesize( $file['tmp_name'] );
	if ( ! $size || $size[0] < 128 || $size[1] < 128 ) {
		return new WP_Error( 'pvc_avatar_dimensions', 'The photo is too small. Use at least 128 by 128 pixels.', array( 'status' => 400 ) );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$_FILES['pv_avatar'] = $file;
	$id                  = media_handle_upload( 'pv_avatar', 0, array( 'post_title' => 'Avatar ' . $user->display_name ) );
	if ( is_wp_error( $id ) ) {
		return new WP_Error( 'pvc_avatar_failed', $id->get_error_message(), array( 'status' => 500 ) );
	}
	$old = (int) get_user_meta( $user->ID, 'pv_avatar_id', true );
	update_user_meta( $user->ID, 'pv_avatar_id', (int) $id );
	if ( $old && $old !== (int) $id ) {
		wp_delete_attachment( $old, true );
	}
	return rest_ensure_response(
		array(
			'html'    => pvc_avatar_html( $user ),
			'message' => 'Profile photo updated.',
		)
	);
}

function pvc_avatar_delete() {
	$user = wp_get_current_user();
	$old  = (int) get_user_meta( $user->ID, 'pv_avatar_id', true );
	if ( $old ) {
		wp_delete_attachment( $old, true );
	}
	delete_user_meta( $user->ID, 'pv_avatar_id' );
	return rest_ensure_response(
		array(
			'html'    => pvc_avatar_html( $user ),
			'message' => 'Profile photo removed.',
		)
	);
}

function pvc_register_avatar_routes() {
	register_rest_route(
		'pvc/v1',
		'/avatar',
		array(
			array(
				'methods'             => 'POST',
				'callback'            => 'pvc_avatar_upload',
				'permission_callback' => 'is_user_logged_in',
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => 'pvc_avatar_delete',
				'permission_callback' => 'is_user_logged_in',
			),
		)
	);
}
add_action( 'rest_api_init', 'pvc_register_avatar_routes' );
