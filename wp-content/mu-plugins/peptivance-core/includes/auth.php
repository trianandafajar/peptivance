<?php

defined( 'ABSPATH' ) || exit;

function pvc_auth_active() {
	return function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in();
}

function pvc_auth_template( $template ) {
	if ( pvc_auth_active() ) {
		return PVC_DIR . 'templates/auth.php';
	}
	return $template;
}
add_filter( 'template_include', 'pvc_auth_template', 99 );

function pvc_auth_body_class( $classes ) {
	if ( pvc_auth_active() ) {
		$classes[] = 'pv-auth';
	}
	return $classes;
}
add_filter( 'body_class', 'pvc_auth_body_class' );

function pvc_auth_locate( $template, $template_name, $template_path ) {
	$file = PVC_DIR . 'templates/woocommerce/' . $template_name;
	if ( 0 === strpos( $template_name, 'myaccount/' ) && file_exists( $file ) ) {
		return $file;
	}
	return $template;
}
add_filter( 'woocommerce_locate_template', 'pvc_auth_locate', 20, 3 );

function pvc_auth_enqueue() {
	if ( ! pvc_auth_active() ) {
		return;
	}
	$css = PVC_DIR . 'assets/css/auth.css';
	wp_enqueue_style( 'pvc-auth', PVC_URL . 'assets/css/auth.css', array( 'pvc-site' ), file_exists( $css ) ? filemtime( $css ) : PVC_VERSION );
	$js = PVC_DIR . 'assets/js/auth.js';
	wp_enqueue_script( 'pvc-auth', PVC_URL . 'assets/js/auth.js', array(), file_exists( $js ) ? filemtime( $js ) : PVC_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'pvc_auth_enqueue', 30 );

function pvc_register_url( $url ) {
	return function_exists( 'wc_get_page_permalink' ) ? add_query_arg( 'mode', 'register', wc_get_page_permalink( 'myaccount' ) ) : $url;
}
add_filter( 'register_url', 'pvc_register_url' );

function pvc_wp_login_redirect() {
	if ( 'GET' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || ! function_exists( 'wc_get_page_permalink' ) ) { // phpcs:ignore
		return;
	}
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( in_array( $action, array( 'lostpassword', 'retrievepassword' ), true ) ) {
		wp_safe_redirect( wc_lostpassword_url() );
		exit;
	}
	if ( 'register' === $action ) {
		wp_safe_redirect( add_query_arg( 'mode', 'register', wc_get_page_permalink( 'myaccount' ) ) );
		exit;
	}
}
add_action( 'login_init', 'pvc_wp_login_redirect' );

function pvc_register_without_policy_text() {
	remove_action( 'woocommerce_register_form', 'wc_registration_privacy_policy_text', 20 );
}
add_action( 'wp', 'pvc_register_without_policy_text' );

function pvc_register_require_agree( $error, $username, $password, $email ) {
	if ( empty( $_POST['pv_agree'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$error->add( 'pv_agree', 'Please confirm that you agree to the privacy policy.' );
	}
	return $error;
}
add_filter( 'woocommerce_process_registration_errors', 'pvc_register_require_agree', 10, 4 );
