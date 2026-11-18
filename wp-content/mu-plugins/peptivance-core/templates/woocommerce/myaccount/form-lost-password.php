<?php

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_lost_password_form' );
?>
<div class="pau-card">
	<section class="pau-panel">
		<h1 class="pau-title">Forgot your password?</h1>
		<p class="pau-lead">Enter the email address or username on your account and we will send you a link to create a new password.</p>
		<form method="post" class="pau-form woocommerce-ResetPassword lost_reset_password">
			<div class="pau-field">
				<label for="user_login">Username or email address</label>
				<input type="text" class="pau-input" name="user_login" id="user_login" autocomplete="username" required aria-required="true">
			</div>
			<?php do_action( 'woocommerce_lostpassword_form' ); ?>
			<input type="hidden" name="wc_reset_password" value="true">
			<?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>
			<button type="submit" class="pau-submit" value="Reset password"><span>Send reset link</span></button>
		</form>
		<p class="pau-switch"><a class="pau-link" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">&larr; Back to log in</a></p>
	</section>
</div>
<?php
do_action( 'woocommerce_after_lost_password_form' );
