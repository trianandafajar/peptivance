<?php

defined( 'ABSPATH' ) || exit;

function pvc_plus_icon() {
	return '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>';
}

function pvc_cart_icon() {
	return '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h2.2l2 11h10.2l2-8H6.4"/><circle cx="9.5" cy="19.5" r="1.4"/><circle cx="16.5" cy="19.5" r="1.4"/></svg>';
}

function pvc_products_shortcode( $atts ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return '';
	}
	$atts = shortcode_atts(
		array(
			'limit'    => 4,
			'category' => '',
			'orderby'  => 'menu_order',
			'order'    => 'ASC',
			'ids'      => '',
		),
		$atts,
		'pv_products'
	);
	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $atts['limit'],
		'orderby'        => sanitize_key( $atts['orderby'] ),
		'order'          => 'DESC' === strtoupper( $atts['order'] ) ? 'DESC' : 'ASC',
	);
	if ( $atts['category'] ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['category'] ) ),
			),
		);
	}
	if ( $atts['ids'] ) {
		$args['post__in'] = array_map( 'intval', explode( ',', $atts['ids'] ) );
		$args['orderby']  = 'post__in';
	}
	$query = new WP_Query( $args );
	if ( ! $query->have_posts() ) {
		return '';
	}
	wp_enqueue_script( 'wc-add-to-cart' );
	ob_start();
	echo '<ul class="pv-products">';
	while ( $query->have_posts() ) {
		$query->the_post();
		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) {
			continue;
		}
		$in_stock = $product->is_in_stock();
		echo '<li class="pv-card">';
		echo '<div class="pv-card__media">';
		echo '<a class="pv-card__link" href="' . esc_url( get_permalink() ) . '" aria-label="' . esc_attr( $product->get_name() ) . '">';
		echo wp_kses_post(
			$product->get_image(
				'large',
				array(
					'class'   => 'pv-card__img',
					'loading' => 'lazy',
				)
			)
		);
		echo '</a>';
		echo '<span class="pv-badge' . ( $in_stock ? '' : ' is-out' ) . '">' . ( $in_stock ? 'In stock' : 'Sold out' ) . '</span>';
		if ( $product->is_purchasable() && $in_stock && $product->is_type( 'simple' ) ) {
			$in_cart = function_exists( 'WC' ) && WC()->cart && WC()->cart->find_product_in_cart( WC()->cart->generate_cart_id( $product->get_id() ) );
			printf(
				'<a href="%s" data-quantity="1" class="pv-card__add button product_type_simple add_to_cart_button%s" data-product_id="%d" data-product_sku="%s" data-add-url="%s" data-name="%s" aria-label="%s" rel="nofollow">%s</a>',
				esc_url( $in_cart ? wc_get_cart_url() : $product->add_to_cart_url() ),
				$in_cart ? ' is-in-cart' : ' ajax_add_to_cart',
				(int) $product->get_id(),
				esc_attr( $product->get_sku() ),
				esc_url( $product->add_to_cart_url() ),
				esc_attr( $product->get_name() ),
				esc_attr( $in_cart ? sprintf( 'View %s in your cart', $product->get_name() ) : sprintf( 'Add %s to cart', $product->get_name() ) ),
				$in_cart ? pvc_cart_icon() : pvc_plus_icon()
			);
		}
		echo '</div>';
		echo '<div class="pv-card__row"><a class="pv-card__title" href="' . esc_url( get_permalink() ) . '">' . esc_html( $product->get_name() ) . '</a><span class="pv-card__price">' . wp_kses_post( $product->get_price_html() ) . '</span></div>';
		echo '</li>';
	}
	echo '</ul>';
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'pv_products', 'pvc_products_shortcode' );

function pvc_coa_shortcode( $atts ) {
	$atts  = shortcode_atts( array( 'limit' => 3 ), $atts, 'pv_coa' );
	$query = new WP_Query(
		array(
			'post_type'      => 'pv_coa',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $atts['limit'],
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	if ( ! $query->have_posts() ) {
		return '';
	}
	ob_start();
	echo '<div class="pv-coa-list">';
	while ( $query->have_posts() ) {
		$query->the_post();
		$id     = get_the_ID();
		$purity = get_post_meta( $id, '_pv_coa_purity', true );
		$meta   = array_filter(
			array(
				get_post_meta( $id, '_pv_coa_batch', true ),
				get_post_meta( $id, '_pv_coa_method', true ),
				get_post_meta( $id, '_pv_coa_date', true ),
			)
		);
		$url    = get_post_meta( $id, '_pv_coa_url', true );
		$width  = max( 0, min( 100, (float) $purity ) );
		echo '<article class="pv-coa-row">';
		echo '<div class="pv-coa-row__main"><h3 class="pv-coa-row__name">' . esc_html( get_the_title() ) . '</h3><p class="pv-coa-row__meta">' . esc_html( implode( ' · ', $meta ) ) . '</p></div>';
		echo '<div class="pv-coa-row__score"><span class="pv-coa-row__value">' . esc_html( $purity ) . '%</span><span class="pv-coa-row__bar"><i style="width:' . esc_attr( $width ) . '%"></i></span><span class="pv-coa-row__label">Purity by HPLC</span></div>';
		if ( $url && '#' !== $url ) {
			echo '<a class="pv-coa-row__link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">View report</a>';
		} else {
			echo '<span class="pv-coa-row__link is-muted">Report on request</span>';
		}
		echo '</article>';
	}
	echo '</div>';
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'pv_coa', 'pvc_coa_shortcode' );

function pvc_chips_shortcode() {
	if ( ! function_exists( 'wc_get_page_permalink' ) ) {
		return '';
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'orderby'    => 'term_id',
		)
	);
	if ( is_wp_error( $terms ) ) {
		return '';
	}
	$current = is_tax( 'product_cat' ) ? get_queried_object_id() : 0;
	$out     = '<nav class="pv-chips" aria-label="Product categories">';
	$out    .= '<a class="pv-chip' . ( $current ? '' : ' is-active' ) . '" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">All products</a>';
	foreach ( $terms as $term ) {
		if ( 'uncategorized' === $term->slug ) {
			continue;
		}
		$out .= '<a class="pv-chip' . ( (int) $term->term_id === (int) $current ? ' is-active' : '' ) . '" href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '<span>' . (int) $term->count . '</span></a>';
	}
	return $out . '</nav>';
}
add_shortcode( 'pv_category_chips', 'pvc_chips_shortcode' );

function pvc_archive_title_shortcode() {
	if ( function_exists( 'woocommerce_page_title' ) && ( is_woocommerce() || is_shop() ) ) {
		return '<h1 class="pv-archive-title">' . esc_html( woocommerce_page_title( false ) ) . '</h1>';
	}
	return '<h1 class="pv-archive-title">' . esc_html( wp_strip_all_tags( get_the_archive_title() ) ) . '</h1>';
}
add_shortcode( 'pv_archive_title', 'pvc_archive_title_shortcode' );

function pvc_related_shortcode( $atts ) {
	$atts  = shortcode_atts( array( 'limit' => 4 ), $atts, 'pv_related' );
	$id    = get_the_ID();
	$limit = (int) $atts['limit'];
	$ids   = function_exists( 'wc_get_related_products' ) ? wc_get_related_products( $id, $limit ) : array();
	if ( count( $ids ) < $limit ) {
		$more = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'fields'         => 'ids',
				'post__not_in'   => array_merge( array( $id ), $ids ),
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);
		$ids  = array_slice( array_merge( $ids, $more ), 0, $limit );
	}
	if ( ! $ids ) {
		return '';
	}
	return pvc_products_shortcode(
		array(
			'ids'   => implode( ',', $ids ),
			'limit' => $limit,
		)
	);
}
add_shortcode( 'pv_related', 'pvc_related_shortcode' );

function pvc_facts_shortcode() {
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
	if ( ! $product ) {
		return '';
	}
	$pid  = $product->get_id();
	$rows = array(
		'Purity (HPLC)' => get_post_meta( $pid, '_pv_purity', true ),
		'Vial size'     => get_post_meta( $pid, '_pv_size', true ),
		'Batch number'  => get_post_meta( $pid, '_pv_batch', true ),
		'Form'          => 'Lyophilised powder',
	);
	$coa  = get_post_meta( $pid, '_pv_coa', true );
	if ( ! $coa ) {
		$page = get_page_by_path( 'testing-quality' );
		$coa  = $page ? get_permalink( $page ) : '';
	}
	$out = '<dl class="pv-facts-list">';
	foreach ( $rows as $label => $value ) {
		if ( '' === $value ) {
			continue;
		}
		if ( 'Purity (HPLC)' === $label ) {
			$value .= '%';
		}
		$out .= '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>';
	}
	$out .= '</dl>';
	if ( $coa ) {
		$out .= '<a class="pv-coa-btn" href="' . esc_url( $coa ) . '">View certificate of analysis</a>';
	}
	return $out;
}
add_shortcode( 'pv_product_facts', 'pvc_facts_shortcode' );

function pvc_product_title_shortcode() {
	return '<h1 class="product_title entry-title">' . esc_html( get_the_title() ) . '</h1>';
}
add_shortcode( 'pv_product_title', 'pvc_product_title_shortcode' );
