<?php
/**
 * Server-to-server calls to Helder. The secret and the koppel-id are only sent here,
 * never to the browser, and never logged.
 *
 * @package Helder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Talks to the Helder API.
 */
class Helder_Api {

	/**
	 * POSTs JSON to Helder.
	 *
	 * @param string $pad  Path below the Helder app URL.
	 * @param array  $body JSON body.
	 * @return array{status: int, body: mixed}|WP_Error
	 */
	private static function post( $pad, array $body ) {
		$antwoord = wp_remote_post(
			untrailingslashit( HELDER_APP_URL ) . $pad,
			array(
				'timeout' => 15,
				'headers' => array(
					'content-type' => 'application/json',
					'accept'       => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $antwoord ) ) {
			return $antwoord;
		}
		return array(
			'status' => (int) wp_remote_retrieve_response_code( $antwoord ),
			'body'   => json_decode( wp_remote_retrieve_body( $antwoord ), true ),
		);
	}

	/**
	 * Exchanges the one-time code for the connection.
	 *
	 * @param string $code One-time code from Helder.
	 * @return array|WP_Error The connection data.
	 */
	public static function wissel_code( $code ) {
		$uit = self::post(
			'/api/wordpress/koppel',
			array(
				'code' => $code,
				'site' => home_url( '/' ),
			)
		);
		if ( is_wp_error( $uit ) ) {
			return $uit;
		}
		$body = $uit['body'];
		if ( 200 !== $uit['status'] || ! is_array( $body ) || empty( $body['sleutel'] ) || empty( $body['koppelId'] ) || empty( $body['klantLoginGeheim'] ) ) {
			return new WP_Error( 'helder_code', __( 'Helder did not accept the connection. Please try again.', 'helder' ) );
		}
		return array(
			'sleutel'            => sanitize_text_field( $body['sleutel'] ),
			'koppel_id'          => sanitize_text_field( $body['koppelId'] ),
			'klant_login_geheim' => (string) $body['klantLoginGeheim'],
			'host'               => (string) wp_parse_url( home_url(), PHP_URL_HOST ),
			// WordPress core confirms the email address of new accounts; WooCommerce can let people
			// register without that, so there the owner turns this on deliberately.
			'klanttoken'         => ! class_exists( 'WooCommerce' ),
		);
	}

	/**
	 * Asks Helder whether the connection still exists, and whether Helder can read WooCommerce.
	 *
	 * @param array $koppeling The stored connection.
	 * @return array{gekoppeld: bool, woocommerce: bool}|null Null when Helder could not be reached.
	 */
	public static function status( array $koppeling ) {
		$uit = self::post(
			'/api/wordpress/status',
			array(
				'sleutel'  => $koppeling['sleutel'],
				'koppelId' => $koppeling['koppel_id'],
			)
		);
		if ( is_wp_error( $uit ) || 200 !== $uit['status'] || ! is_array( $uit['body'] ) || ! isset( $uit['body']['gekoppeld'] ) ) {
			return null;
		}
		return array(
			'gekoppeld'   => true === $uit['body']['gekoppeld'],
			'woocommerce' => isset( $uit['body']['woocommerce'] ) && true === $uit['body']['woocommerce'],
		);
	}

	/**
	 * Asks Helder whether the connection still exists.
	 *
	 * @param array $koppeling The stored connection.
	 * @return bool|null True or false, or null when Helder could not be reached.
	 */
	public static function nog_gekoppeld( array $koppeling ) {
		$status = self::status( $koppeling );
		return null === $status ? null : $status['gekoppeld'];
	}

	/**
	 * Asks Helder for a short-lived signed state for WooCommerce's approval flow.
	 *
	 * @param array  $koppeling  The stored connection.
	 * @param string $winkel_url The shop's home URL.
	 * @return string|WP_Error The state.
	 */
	public static function woo_state( array $koppeling, $winkel_url ) {
		$uit = self::post(
			'/api/wordpress/woocommerce/start',
			array(
				'sleutel'   => $koppeling['sleutel'],
				'koppelId'  => $koppeling['koppel_id'],
				'winkelUrl' => $winkel_url,
			)
		);
		if ( is_wp_error( $uit ) ) {
			return $uit;
		}
		if ( 200 !== $uit['status'] || ! is_array( $uit['body'] ) || empty( $uit['body']['state'] ) ) {
			return new WP_Error( 'helder_woo', __( 'Helder did not accept the request.', 'helder' ) );
		}
		return (string) $uit['body']['state'];
	}

	/**
	 * Tells Helder to stop reading WooCommerce for this site. The chat stays connected.
	 *
	 * @param array $koppeling The stored connection.
	 */
	public static function woo_intrekken( array $koppeling ) {
		self::post(
			'/api/wordpress/woocommerce/intrekken',
			array(
				'sleutel'  => $koppeling['sleutel'],
				'koppelId' => $koppeling['koppel_id'],
			)
		);
	}

	/**
	 * Tells Helder the site disconnected.
	 *
	 * @param array $koppeling The stored connection.
	 */
	public static function ontkoppel( array $koppeling ) {
		self::post(
			'/api/wordpress/ontkoppel',
			array(
				'sleutel'  => $koppeling['sleutel'],
				'koppelId' => $koppeling['koppel_id'],
			)
		);
	}
}
