<?php

defined( 'ABSPATH' ) || exit;

$user      = wp_get_current_user();
$ids       = get_option( 'pv_media_ids' );
$logo      = ! empty( $ids['logo_white'] ) ? wp_get_attachment_image( (int) $ids['logo_white'], 'medium', false, array( 'class' => 'pva-logo__img', 'alt' => get_bloginfo( 'name' ) ) ) : esc_html( get_bloginfo( 'name' ) );
$icon      = ! empty( $ids['icon'] ) ? wp_get_attachment_image( (int) $ids['icon'], 'thumbnail', false, array( 'class' => 'pva-logo__icon', 'alt' => '' ) ) : '';
$items     = wc_get_account_menu_items();
$account   = wc_get_page_permalink( 'myaccount' );
list( $current, $title ) = pvc_app_title();
$order_n   = (int) wc_get_customer_order_count( $user->ID );
$cart_n    = function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;

$is_page   = pvc_app_page_active();
$content   = '';
if ( $is_page ) {
	$content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( get_queried_object_id() );
	if ( ! $content ) {
		$content = apply_filters( 'the_content', get_post_field( 'post_content', get_queried_object_id() ) );
	}
}
remove_action( 'woocommerce_account_navigation', 'woocommerce_account_navigation' );
remove_action( 'woocommerce_before_account_navigation', 'pvc_account_user_card' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'pv-account-in' ); ?>>
<?php wp_body_open(); ?>
<div class="pva" data-pva>
	<aside class="pva-side" id="pva-side" aria-label="Account navigation">
		<div class="pva-side__top">
			<a class="pva-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> home"><?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<button class="pva-side__close" type="button" aria-label="Close menu" data-pva-close><?php echo pvc_app_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</div>
		<nav class="pva-nav">
			<p class="pva-nav__label">Account</p>
			<ul>
				<?php foreach ( $items as $endpoint => $label ) : ?>
					<?php
					if ( 'customer-logout' === $endpoint ) {
						continue;
					}
					$is_current = $endpoint === $current || ( 'view-order' === $current && 'orders' === $endpoint );
					$count      = '';
					if ( 'orders' === $endpoint && $order_n ) {
						$count = $order_n;
					}
					?>
					<li>
						<a class="pva-nav__link<?php echo $is_current ? ' is-active' : ''; ?>" href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?>>
							<?php echo pvc_app_icon( $endpoint ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span><?php echo esc_html( $label ); ?></span>
							<?php if ( '' !== $count ) : ?>
								<em class="pva-nav__count" data-pva-count="<?php echo esc_attr( $endpoint ); ?>"><?php echo (int) $count; ?></em>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="pva-nav__label">Store</p>
			<ul>
				<li><a class="pva-nav__link" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php echo pvc_app_icon( 'shop' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>Continue shopping</span></a></li>
				<li><a class="pva-nav__link<?php echo in_array( $current, array( 'cart', 'checkout' ), true ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( wc_get_cart_url() ); ?>"<?php echo in_array( $current, array( 'cart', 'checkout' ), true ) ? ' aria-current="page"' : ''; ?>><?php echo pvc_app_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>Cart</span><em class="pva-nav__count" data-pva-cart><?php echo (int) $cart_n; ?></em></a></li>
			</ul>
		</nav>
		<div class="pva-side__bottom">
			<div class="pva-user">
				<span class="pva-user__avatar"><?php echo pvc_avatar_html( $user ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="pva-user__text"><strong><?php echo esc_html( $user->display_name ); ?></strong><span><?php echo esc_html( $user->user_email ); ?></span></span>
			</div>
			<button class="pva-logout" type="button" data-pv-open="logout"><?php echo pvc_app_icon( 'customer-logout' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>Log out</span></button>
		</div>
	</aside>
	<div class="pva-scrim" data-pva-close></div>
	<div class="pva-body">
		<header class="pva-top">
			<button class="pva-burger" type="button" aria-label="Open menu" aria-controls="pva-side" aria-expanded="false" data-pva-open><?php echo pvc_app_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<a class="pva-top__icon" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Home"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<div class="pva-top__title">
				<?php if ( 'view-order' === $current ) : ?>
					<a class="pva-top__crumb" href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>">&larr; Orders</a>
				<?php else : ?>
					<p class="pva-top__crumb"><?php echo $is_page ? 'Store' : 'My account'; ?></p>
				<?php endif; ?>
				<h1><?php echo esc_html( $title ); ?></h1>
			</div>
			<div class="pva-top__tools">
				<button class="pva-tool" type="button" aria-label="Search products" data-pv-open="search"><?php echo pvc_icon_search(); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<a class="pva-tool pva-tool--cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="Cart"><?php echo pvc_app_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><em data-pva-cart><?php echo (int) $cart_n; ?></em></a>
				<a class="pva-top__shop" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Back to shop</a>
			</div>
		</header>
		<main class="pva-main" id="main">
			<?php if ( $is_page ) : ?>
				<div class="pva-content pva-content--page"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php else : ?>
				<div class="elementor-widget-woocommerce-my-account pva-content">
					<?php echo do_shortcode( '[woocommerce_my_account]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			<?php endif; ?>
		</main>
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
