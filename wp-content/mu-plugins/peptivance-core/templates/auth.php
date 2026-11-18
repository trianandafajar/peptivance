<?php

defined( 'ABSPATH' ) || exit;

$ids   = get_option( 'pv_media_ids' );
$logo  = ! empty( $ids['logo_white'] ) ? wp_get_attachment_image( (int) $ids['logo_white'], 'medium', false, array( 'class' => 'pau-logo__img', 'alt' => get_bloginfo( 'name' ) ) ) : esc_html( get_bloginfo( 'name' ) );
$logo2 = ! empty( $ids['logo_dark'] ) ? wp_get_attachment_image( (int) $ids['logo_dark'], 'medium', false, array( 'class' => 'pau-logo__img', 'alt' => get_bloginfo( 'name' ) ) ) : esc_html( get_bloginfo( 'name' ) );
$privacy = function_exists( 'get_privacy_policy_url' ) && get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/privacy-policy/' );
$refund  = home_url( '/refund_returns/' );
$content = do_shortcode( '[woocommerce_my_account]' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'pv-auth' ); ?>>
<?php wp_body_open(); ?>
<div class="pau">
	<aside class="pau-brand" aria-hidden="false">
		<a class="pau-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> home"><?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		<div class="pau-brand__copy">
			<h2>Research-grade peptides, documented batch by batch.</h2>
			<ul class="pau-points">
				<li>Certificate of Analysis with every batch</li>
				<li>HPLC purity data on every product</li>
				<li>Tracked UK shipping, free over $75</li>
			</ul>
		</div>
		<div class="pau-hero" aria-hidden="true"><?php echo file_get_contents( PVC_DIR . 'assets/img/spectrum.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput, WordPress.WP.AlternativeFunctions ?></div>
	</aside>
	<main class="pau-main" id="main">
		<header class="pau-top">
			<a class="pau-top__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> home"><?php echo $logo2; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<a class="pau-back" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span aria-hidden="true">&larr;</span> Back to store</a>
		</header>
		<div class="pau-stage">
			<div class="pau-content woocommerce-account"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		</div>
	</main>
</div>
<?php wp_footer(); ?>
</body>
</html>
