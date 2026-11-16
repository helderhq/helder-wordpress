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
delete_option( 'helder_koppeling' );
wp_clear_scheduled_hook( 'helder_controle' );
