<?php

defined( 'ABSPATH' ) || exit;

$register_on = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
$mode        = ( $register_on && ( isset( $_POST['register'] ) || ( isset( $_GET['mode'] ) && 'register' === $_GET['mode'] ) ) ) ? 'register' : 'login'; // phpcs:ignore WordPress.Security.NonceVerification
$eye         = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/></svg>';

do_action( 'woocommerce_before_customer_login_form' );
?>
<div class="pau-card" data-pv-auth data-mode="<?php echo esc_attr( $mode ); ?>">
	<?php if ( $register_on ) : ?>
		<div class="pau-tabs" role="tablist" aria-label="Account access">
			<button type="button" class="pau-tab<?php echo 'login' === $mode ? ' is-active' : ''; ?>" role="tab" id="pau-tab-login" aria-controls="pau-panel-login" aria-selected="<?php echo 'login' === $mode ? 'true' : 'false'; ?>" data-pv-tab="login">Log in</button>
			<button type="button" class="pau-tab<?php echo 'register' === $mode ? ' is-active' : ''; ?>" role="tab" id="pau-tab-register" aria-controls="pau-panel-register" aria-selected="<?php echo 'register' === $mode ? 'true' : 'false'; ?>" data-pv-tab="register">Create account</button>
		</div>
	<?php endif; ?>

	<section class="pau-panel" id="pau-panel-login" role="tabpanel" aria-labelledby="pau-tab-login" data-pv-panel="login"<?php echo 'login' === $mode ? '' : ' hidden'; ?>>
		<h1 class="pau-title">Welcome back</h1>
		<p class="pau-lead">Log in to track orders, manage addresses and update your details.</p>
		<form class="pau-form woocommerce-form woocommerce-form-login login" method="post" novalidate>
			<?php do_action( 'woocommerce_login_form_start' ); ?>
			<div class="pau-field">
				<label for="username">Username or email address</label>
				<input type="text" class="pau-input" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore ?>" required aria-required="true">
			</div>
			<div class="pau-field">
				<label for="password">Password</label>
				<div class="pau-pass">
					<input type="password" class="pau-input" name="password" id="password" autocomplete="current-password" required aria-required="true">
					<button type="button" class="pau-eye" aria-label="Show password" aria-pressed="false" data-pv-eye><?php echo $eye; // phpcs:ignore ?></button>
				</div>
			</div>
			<?php do_action( 'woocommerce_login_form' ); ?>
			<div class="pau-row">
				<label class="pau-check"><input name="rememberme" type="checkbox" id="rememberme" value="forever"><span>Remember me</span></label>
				<a class="pau-link" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Forgot password?</a>
			</div>
			<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
			<button type="submit" class="pau-submit" name="login" value="Log in"><span>Log in</span></button>
			<?php do_action( 'woocommerce_login_form_end' ); ?>
		</form>
		<?php if ( $register_on ) : ?>
			<p class="pau-switch">New to Peptivance? <button type="button" class="pau-link pau-link--btn" data-pv-tab="register">Create an account</button></p>
		<?php endif; ?>
	</section>

	<?php if ( $register_on ) : ?>
		<section class="pau-panel" id="pau-panel-register" role="tabpanel" aria-labelledby="pau-tab-register" data-pv-panel="register"<?php echo 'register' === $mode ? '' : ' hidden'; ?>>
			<h1 class="pau-title">Create your account</h1>
			<p class="pau-lead">Save your details for faster checkout and keep every order in one place.</p>
			<form method="post" class="pau-form woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> novalidate>
				<?php do_action( 'woocommerce_register_form_start' ); ?>
				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
					<div class="pau-field">
						<label for="reg_username">Username</label>
						<input type="text" class="pau-input" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore ?>" required aria-required="true">
					</div>
				<?php endif; ?>
				<div class="pau-field">
					<label for="reg_email">Email address</label>
					<input type="email" class="pau-input" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; // phpcs:ignore ?>" required aria-required="true">
				</div>
				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
					<div class="pau-field">
						<label for="reg_password">Password</label>
						<div class="pau-pass">
							<input type="password" class="pau-input" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" aria-describedby="reg_password_hint">
							<button type="button" class="pau-eye" aria-label="Show password" aria-pressed="false" data-pv-eye><?php echo $eye; // phpcs:ignore ?></button>
						</div>
						<p class="pau-hint" id="reg_password_hint">Use at least 8 characters with a mix of letters and numbers.</p>
					</div>
				<?php else : ?>
					<p class="pau-hint">A link to set your password will be sent to your email address.</p>
				<?php endif; ?>
				<?php do_action( 'woocommerce_register_form' ); ?>
				<?php $privacy = function_exists( 'get_privacy_policy_url' ) && get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/privacy-policy/' ); ?>
				<label class="pau-check pau-check--agree"><input type="checkbox" name="pv_agree" value="1" required aria-required="true"<?php echo ! empty( $_POST['pv_agree'] ) ? ' checked' : ''; // phpcs:ignore ?>><span>I have read and agree to the <a class="pau-link" href="<?php echo esc_url( $privacy ); ?>" target="_blank" rel="noopener">privacy policy</a>.</span></label>
				<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
				<button type="submit" class="pau-submit" name="register" value="Register"><span>Create account</span></button>
				<?php do_action( 'woocommerce_register_form_end' ); ?>
			</form>
			<p class="pau-switch">Already have an account? <button type="button" class="pau-link pau-link--btn" data-pv-tab="login">Log in</button></p>
		</section>
	<?php endif; ?>
</div>
<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
