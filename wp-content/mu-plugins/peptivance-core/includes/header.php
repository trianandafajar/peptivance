<?php

defined( 'ABSPATH' ) || exit;

function pvc_icon_search() {
	return '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/></svg>';
}

function pvc_icon_user() {
	return '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>';
}

function pvc_icon_close() {
	return '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>';
}

function pvc_has_gravatar( $email ) {
	$key    = 'pvc_grav_' . md5( strtolower( trim( $email ) ) );
	$cached = get_transient( $key );
	if ( false !== $cached ) {
		return '1' === $cached;
	}
	$url      = 'https://www.gravatar.com/avatar/' . md5( strtolower( trim( $email ) ) ) . '?d=404&s=96';
	$response = wp_remote_head( $url, array( 'timeout' => 2 ) );
	$has      = ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response );
	set_transient( $key, $has ? '1' : '0', DAY_IN_SECONDS );
	return $has;
}

function pvc_avatar_html( $user ) {
	$custom = (int) get_user_meta( $user->ID, 'pv_avatar_id', true );
	if ( $custom ) {
		$src = wp_get_attachment_image_url( $custom, 'thumbnail' );
		if ( $src ) {
			return '<img class="pv-avatar-img" src="' . esc_url( $src ) . '" alt="' . esc_attr( $user->display_name ) . '" width="40" height="40">';
		}
	}
	if ( pvc_has_gravatar( $user->user_email ) ) {
		return '<img class="pv-avatar-img" src="' . esc_url( get_avatar_url( $user->ID, array( 'size' => 96 ) ) ) . '" alt="' . esc_attr( $user->display_name ) . '" width="40" height="40">';
	}
	$name    = trim( $user->display_name ? $user->display_name : $user->user_login );
	$initial = function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( $name, 0, 1 ) ) : strtoupper( substr( $name, 0, 1 ) );
	return '<span class="pv-avatar-initial" aria-hidden="true">' . esc_html( $initial ) . '</span>';
}

function pvc_header_tools_shortcode() {
	$account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
	$out     = '<div class="pv-tools">';
	$out    .= '<button class="pv-tool pv-tool--search" type="button" aria-label="Search products" data-pv-open="search">' . pvc_icon_search() . '</button>';
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$out .= '<div class="pv-user">';
		$out .= '<button class="pv-user__toggle" type="button" aria-haspopup="menu" aria-expanded="false" aria-label="Account menu">' . pvc_avatar_html( $user ) . '</button>';
		$out .= '<div class="pv-user__menu" role="menu" hidden>';
		$out .= '<div class="pv-user__head"><strong>' . esc_html( $user->display_name ) . '</strong><span>' . esc_html( $user->user_email ) . '</span></div>';
		$out .= '<a class="pv-user__item" role="menuitem" href="' . esc_url( $account ) . '">Manage account</a>';
		$out .= '<button class="pv-user__item pv-user__item--danger" type="button" role="menuitem" data-pv-open="logout">Log out</button>';
		$out .= '</div></div>';
	} else {
		$out .= '<a class="pv-tool" href="' . esc_url( $account ) . '" aria-label="My account">' . pvc_icon_user() . '</a>';
	}
	return $out . '</div>';
}
add_shortcode( 'pv_header_tools', 'pvc_header_tools_shortcode' );

function pvc_search_suggestions() {
	$out   = '';
	$names = array();
	if ( function_exists( 'wc_get_products' ) ) {
		$products = wc_get_products(
			array(
				'limit'   => 6,
				'status'  => 'publish',
				'orderby' => 'menu_order',
				'order'   => 'ASC',
			)
		);
		foreach ( $products as $p ) {
			$names[] = $p->get_name();
		}
	}
	if ( $names ) {
		$out .= '<p class="pv-search__label">Popular searches</p><div class="pv-search__chips pv-drag">';
		foreach ( $names as $n ) {
			$out .= '<button class="pv-search__chip" type="button" data-pv-query="' . esc_attr( $n ) . '">' . esc_html( $n ) . '</button>';
		}
		$out .= '</div>';
	}
	$terms = function_exists( 'wc_get_page_permalink' ) ? get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'term_id' ) ) : array();
	if ( $terms && ! is_wp_error( $terms ) ) {
		$out .= '<p class="pv-search__label">Browse by focus</p><div class="pv-search__links pv-drag">';
		foreach ( $terms as $t ) {
			if ( 'uncategorized' === $t->slug ) {
				continue;
			}
			$out .= '<a class="pv-search__link" href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a>';
		}
		$out .= '</div>';
	}
	return $out;
}

function pvc_modals() {
	if ( is_admin() ) {
		return;
	}
	$logout = function_exists( 'wc_logout_url' ) ? wc_logout_url( home_url( '/' ) ) : wp_logout_url( home_url( '/' ) );
	?>
	<div class="pv-modal" id="pv-search-modal" role="dialog" aria-modal="true" aria-labelledby="pv-search-title" hidden>
		<div class="pv-modal__backdrop" data-pv-close></div>
		<div class="pv-modal__panel pv-modal__panel--search">
			<div class="pv-modal__bar">
				<div>
					<p class="pv-modal__eyebrow">Search</p>
					<h2 class="pv-modal__title" id="pv-search-title">Find a research peptide</h2>
				</div>
				<button class="pv-modal__close" type="button" aria-label="Close search" data-pv-close><?php echo pvc_icon_close(); ?></button>
			</div>
			<form class="pv-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<span class="pv-search__icon"><?php echo pvc_icon_search(); ?></span>
				<input class="pv-search__input" type="search" name="s" placeholder="Search by name, e.g. BPC-157" autocomplete="off" aria-label="Search products">
				<input type="hidden" name="post_type" value="product">
				<button class="pv-search__submit" type="submit">Search</button>
			</form>
			<div class="pv-search__body" aria-live="polite">
				<?php echo pvc_search_suggestions(); ?>
			</div>
		</div>
	</div>
	<div class="pv-modal" id="pv-logout-modal" role="alertdialog" aria-modal="true" aria-labelledby="pv-logout-title" aria-describedby="pv-logout-text" hidden>
		<div class="pv-modal__backdrop" data-pv-close></div>
		<div class="pv-modal__panel pv-modal__panel--confirm">
			<h2 class="pv-modal__title" id="pv-logout-title">Log out?</h2>
			<p class="pv-modal__text" id="pv-logout-text">You will need to sign in again to see your orders and account details.</p>
			<div class="pv-modal__actions">
				<button class="pv-btn pv-btn--ghost" type="button" data-pv-close>Cancel</button>
				<a class="pv-btn pv-btn--solid" href="<?php echo esc_url( $logout ); ?>">Log out</a>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'pvc_modals', 5 );

function pvc_enqueue_header_script() {
	$js = PVC_DIR . 'assets/js/header.js';
	wp_enqueue_script( 'pvc-header', PVC_URL . 'assets/js/header.js', array(), file_exists( $js ) ? filemtime( $js ) : PVC_VERSION, true );
	wp_localize_script(
		'pvc-header',
		'pvHeader',
		array(
			'api'    => esc_url_raw( rest_url( 'pvc/v1/search' ) ),
			'search' => esc_url_raw( home_url( '/' ) ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'pvc_enqueue_header_script', 20 );

function pvc_rest_search( $request ) {
	$q = trim( (string) $request->get_param( 'q' ) );
	if ( strlen( $q ) < 2 || ! function_exists( 'wc_get_product' ) ) {
		return rest_ensure_response( array() );
	}
	$query = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 6,
			's'              => $q,
			'no_found_rows'  => true,
		)
	);
	$items = array();
	foreach ( $query->posts as $post ) {
		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			continue;
		}
		$cats    = get_the_terms( $post->ID, 'product_cat' );
		$items[] = array(
			'name'      => html_entity_decode( $product->get_name(), ENT_QUOTES, 'UTF-8' ),
			'permalink' => get_permalink( $post->ID ),
			'image'     => wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ),
			'price'     => html_entity_decode( wp_strip_all_tags( wc_price( $product->get_price() ) ), ENT_QUOTES, 'UTF-8' ),
			'category'  => $cats && ! is_wp_error( $cats ) ? html_entity_decode( $cats[0]->name, ENT_QUOTES, 'UTF-8' ) : '',
		);
	}
	return rest_ensure_response( $items );
}

function pvc_register_search_route() {
	register_rest_route(
		'pvc/v1',
		'/search',
		array(
			'methods'             => 'GET',
			'callback'            => 'pvc_rest_search',
			'permission_callback' => '__return_true',
			'args'                => array(
				'q' => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'pvc_register_search_route' );
