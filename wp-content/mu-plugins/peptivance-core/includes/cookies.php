<?php

defined( 'ABSPATH' ) || exit;

function pvc_cookie_banner() {
	if ( is_admin() ) {
		return;
	}
	$privacy = function_exists( 'get_privacy_policy_url' ) && get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/privacy-policy/' );
	?>
	<div class="pv-consent" id="pv-consent" role="dialog" aria-modal="false" aria-labelledby="pv-consent-title" hidden>
		<h2 class="pv-consent__title" id="pv-consent-title">We use cookies</h2>
		<p class="pv-consent__text">Essential cookies keep your cart and account working. With your permission we also use cookies to understand how the site is used. Read our <a href="<?php echo esc_url( $privacy ); ?>">privacy policy</a>.</p>
		<div class="pv-consent__actions">
			<button type="button" class="pv-consent__btn pv-consent__btn--ghost" data-consent="declined">Essential only</button>
			<button type="button" class="pv-consent__btn pv-consent__btn--solid" data-consent="accepted">Accept all</button>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'pvc_cookie_banner', 5 );

function pvc_consent_script() {
	$js = PVC_DIR . 'assets/js/consent.js';
	wp_enqueue_script( 'pvc-consent', PVC_URL . 'assets/js/consent.js', array(), file_exists( $js ) ? filemtime( $js ) : PVC_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'pvc_consent_script', 30 );
