<?php
/**
 * Stored connection data. Everything lives in one option that is not autoloaded.
 * The customer login secret never leaves the server.
 *
 * @package Helder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the connection.
 */
class Helder_Opslag {

	const OPTIE = 'helder_koppeling';

	/**
	 * The current connection, or null when the site is not connected.
	 *
	 * @return array{sleutel: string, koppel_id: string, klant_login_geheim: string, host: string, klanttoken: bool}|null
	 */
	public static function koppeling() {
		$waarde = get_option( self::OPTIE );
		if ( ! is_array( $waarde ) || empty( $waarde['sleutel'] ) || empty( $waarde['koppel_id'] ) ) {
			return null;
		}
		return array(
			'sleutel'            => (string) $waarde['sleutel'],
			'koppel_id'          => (string) $waarde['koppel_id'],
			'klant_login_geheim' => isset( $waarde['klant_login_geheim'] ) ? (string) $waarde['klant_login_geheim'] : '',
			'host'               => isset( $waarde['host'] ) ? (string) $waarde['host'] : '',
			'klanttoken'         => ! isset( $waarde['klanttoken'] ) || (bool) $waarde['klanttoken'],
		);
	}

	/**
	 * Saves a new connection.
	 *
	 * @param array $koppeling Connection data from the code exchange.
	 */
	public static function bewaar( array $koppeling ) {
		update_option( self::OPTIE, $koppeling, false );
	}

	/**
	 * Turns passing on logged-in visitors on or off.
	 *
	 * @param bool $aan Whether to pass on logged-in visitors.
	 */
	public static function zet_klanttoken( $aan ) {
		$koppeling = self::koppeling();
		if ( null === $koppeling ) {
			return;
		}
		$koppeling['klanttoken'] = (bool) $aan;
		self::bewaar( $koppeling );
	}

	/**
	 * Removes the connection.
	 */
	public static function wis() {
		delete_option( self::OPTIE );
	}
}
