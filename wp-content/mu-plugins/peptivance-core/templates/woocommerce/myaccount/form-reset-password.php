<?php

defined( 'ABSPATH' ) || exit;

$eye = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/></svg>';

do_action( 'woocommerce_before_reset_password_form' );
?>
<div class="pau-card">
	<section class="pau-panel">
		<span class="pau-badge" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="15" r="3.5"/><path d="M10.5 12.5L19 4M16 7l3 3M14 9l2 2"/></svg></span>
		<h1 class="pau-title">Create a new password</h1>
		<p class="pau-lead">Choose a strong password you have not used on this site before.</p>
		<form method="post" class="pau-form woocommerce-ResetPassword lost_reset_password">
			<div class="pau-field">
				<label for="password_1">New password</label>
				<div class="pau-pass">
					<input type="password" class="pau-input" name="password_1" id="password_1" autocomplete="new-password" required aria-required="true">
					<button type="button" class="pau-eye" aria-label="Show password" aria-pressed="false" data-pv-eye><?php echo $eye; // phpcs:ignore ?></button>
				</div>
			</div>
			<div class="pau-field">
				<label for="password_2">Confirm new password</label>
				<div class="pau-pass">
					<input type="password" class="pau-input" name="password_2" id="password_2" autocomplete="new-password" required aria-required="true">
					<button type="button" class="pau-eye" aria-label="Show password" aria-pressed="false" data-pv-eye><?php echo $eye; // phpcs:ignore ?></button>
				</div>
			</div>
			<input type="hidden" name="reset_key" value="<?php echo esc_attr( $args['key'] ); ?>">
			<input type="hidden" name="reset_login" value="<?php echo esc_attr( $args['login'] ); ?>">
			<?php do_action( 'woocommerce_resetpassword_form' ); ?>
			<input type="hidden" name="wc_reset_password" value="true">
			<?php wp_nonce_field( 'reset_password', 'woocommerce-reset-password-nonce' ); ?>
			<button type="submit" class="pau-submit" value="Save"><span>Save new password</span></button>
		</form>
	</section>
</div>
<?php
do_action( 'woocommerce_after_reset_password_form' );
