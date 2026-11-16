<?php
/**
 * Removes everything the plugin stored and tells Helder the site is disconnected.
 *
 * @package Helder
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$helder_koppeling = get_option( 'helder_koppeling' );
if ( is_array( $helder_koppeling ) && ! empty( $helder_koppeling['sleutel'] ) && ! empty( $helder_koppeling['koppel_id'] ) ) {
	$helder_app_url = defined( 'HELDER_APP_URL' ) ? HELDER_APP_URL : 'https://app.helderhq.com';
	wp_remote_post(
		untrailingslashit( $helder_app_url ) . '/api/wordpress/ontkoppel',
		array(
			'timeout'  => 5,
			'blocking' => false,
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'sleutel'  => $helder_koppeling['sleutel'],
					'koppelId' => $helder_koppeling['koppel_id'],
				)
			),
		)
	);
}
// The read-only keys WooCommerce made for Helder (named "Helder - API (<date>)").
global $wpdb;
$helder_tabel = $wpdb->prefix . 'woocommerce_api_keys';
// phpcs:disable WordPress.DB.DirectDatabaseQuery -- WooCommerce has no API to delete keys; it uses $wpdb too.
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $helder_tabel ) ) ) === $helder_tabel ) {
	$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE description LIKE %s', $helder_tabel, $wpdb->esc_like( 'Helder - API (' ) . '%' ) );
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery
delete_option( 'helder_koppeling' );
wp_clear_scheduled_hook( 'helder_controle' );
