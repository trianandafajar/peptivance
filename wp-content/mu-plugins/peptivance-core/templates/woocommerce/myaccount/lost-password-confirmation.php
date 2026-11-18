<?php

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_lost_password_confirmation_message' );
?>
<div class="pau-card">
	<section class="pau-panel pau-panel--center">
		<span class="pau-badge pau-badge--ok" aria-hidden="true"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5.5" width="17" height="13" rx="1.5"/><path d="M4 7l8 6 8-6"/></svg></span>
		<h1 class="pau-title">Check your email</h1>
		<p class="pau-lead"><?php echo esc_html( apply_filters( 'woocommerce_lost_password_confirmation_message', 'We have sent a password reset link to the email address on your account. It can take a few minutes to arrive, so check your spam folder too.' ) ); ?></p>
		<a class="pau-submit pau-submit--link" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><span>Back to log in</span></a>
		<p class="pau-switch">Did not get it? <a class="pau-link" href="<?php echo esc_url( wc_lostpassword_url() ); ?>">Try again</a></p>
	</section>
</div>
<?php
do_action( 'woocommerce_after_lost_password_confirmation_message' );
