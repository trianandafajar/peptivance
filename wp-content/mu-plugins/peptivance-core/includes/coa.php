<?php

defined( 'ABSPATH' ) || exit;

function pvc_register_coa() {
	register_post_type(
		'pv_coa',
		array(
			'labels'        => array(
				'name'          => 'Certificates',
				'singular_name' => 'Certificate',
				'add_new_item'  => 'Add new certificate',
				'edit_item'     => 'Edit certificate',
				'menu_name'     => 'Certificates',
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-awards',
			'menu_position' => 56,
			'supports'      => array( 'title' ),
		)
	);
}
add_action( 'init', 'pvc_register_coa' );

function pvc_coa_fields() {
	return array(
		'_pv_coa_purity' => array( 'Purity (%)', '99.51' ),
		'_pv_coa_batch'  => array( 'Batch number', 'PV-2645' ),
		'_pv_coa_mass'   => array( 'Mass (MS)', '1419.6 Da' ),
		'_pv_coa_method' => array( 'Method', 'HPLC' ),
		'_pv_coa_date'   => array( 'Tested on', '12 Jun 2026' ),
		'_pv_coa_url'    => array( 'Report URL', 'https://' ),
	);
}

function pvc_coa_meta_box() {
	add_meta_box( 'pvc_coa_data', 'Certificate details', 'pvc_coa_meta_box_render', 'pv_coa', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'pvc_coa_meta_box' );

function pvc_coa_meta_box_render( $post ) {
	wp_nonce_field( 'pvc_coa_save', 'pvc_coa_nonce' );
	echo '<table class="form-table">';
	foreach ( pvc_coa_fields() as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );
		echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $field[0] ) . '</label></th><td><input type="text" class="regular-text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $field[1] ) . '"></td></tr>';
	}
	echo '</table>';
}

function pvc_coa_save( $post_id ) {
	if ( ! isset( $_POST['pvc_coa_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pvc_coa_nonce'] ) ), 'pvc_coa_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array_keys( pvc_coa_fields() ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$raw   = wp_unslash( $_POST[ $key ] );
			$value = '_pv_coa_url' === $key ? esc_url_raw( $raw ) : sanitize_text_field( $raw );
			update_post_meta( $post_id, $key, $value );
		}
	}
}
add_action( 'save_post_pv_coa', 'pvc_coa_save' );

function pvc_coa_columns( $columns ) {
	return array(
		'cb'        => $columns['cb'],
		'title'     => 'Peptide',
		'pv_purity' => 'Purity',
		'pv_batch'  => 'Batch',
		'date'      => $columns['date'],
	);
}
add_filter( 'manage_pv_coa_posts_columns', 'pvc_coa_columns' );

function pvc_coa_column_content( $column, $post_id ) {
	if ( 'pv_purity' === $column ) {
		echo esc_html( get_post_meta( $post_id, '_pv_coa_purity', true ) ) . '%';
	}
	if ( 'pv_batch' === $column ) {
		echo esc_html( get_post_meta( $post_id, '_pv_coa_batch', true ) );
	}
}
add_action( 'manage_pv_coa_posts_custom_column', 'pvc_coa_column_content', 10, 2 );
