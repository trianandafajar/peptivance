<?php

defined( 'ABSPATH' ) || exit;

define( 'PVC_DIR', plugin_dir_path( __FILE__ ) . 'peptivance-core/' );
define( 'PVC_URL', plugin_dir_url( __FILE__ ) . 'peptivance-core/' );
define( 'PVC_VERSION', '1.0.0' );

foreach ( array( 'assets', 'products', 'coa', 'shortcodes', 'header', 'account', 'checkout', 'cookies', 'account-app', 'auth' ) as $pvc_file ) {
	require_once PVC_DIR . 'includes/' . $pvc_file . '.php';
}
