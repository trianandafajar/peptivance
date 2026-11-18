<?php

defined( 'ABSPATH' ) || exit;

function pvc_enqueue_assets() {
	$css = PVC_DIR . 'assets/css/site.css';
	wp_enqueue_style( 'pvc-site', PVC_URL . 'assets/css/site.css', array(), file_exists( $css ) ? filemtime( $css ) : PVC_VERSION );
}
add_action( 'wp_enqueue_scripts', 'pvc_enqueue_assets', 20 );

function pvc_preload_font() {
	echo '<link rel="preload" href="' . esc_url( PVC_URL . 'assets/fonts/sora.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
}
add_action( 'wp_head', 'pvc_preload_font', 1 );

function pvc_enqueue_scripts() {
	$js = PVC_DIR . 'assets/js/drag-scroll.js';
	wp_enqueue_script( 'pvc-drag-scroll', PVC_URL . 'assets/js/drag-scroll.js', array(), file_exists( $js ) ? filemtime( $js ) : PVC_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'pvc_enqueue_scripts', 20 );

function pvc_enqueue_cards() {
	$js = PVC_DIR . 'assets/js/cards.js';
	wp_enqueue_script( 'pvc-cards', PVC_URL . 'assets/js/cards.js', array( 'jquery' ), file_exists( $js ) ? filemtime( $js ) : PVC_VERSION, true );
	wp_localize_script(
		'pvc-cards',
		'pvCards',
		array(
			'cartUrl' => function_exists( 'wc_get_cart_url' ) ? esc_url_raw( wc_get_cart_url() ) : '',
			'qtyUrl'  => class_exists( 'WC_AJAX' ) ? esc_url_raw( WC_AJAX::get_endpoint( 'pvc_cart_qty' ) ) : '',
			'nonce'   => wp_create_nonce( 'pvc_cart' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'pvc_enqueue_cards', 25 );
