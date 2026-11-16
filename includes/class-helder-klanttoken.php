<?php
/**
 * Customer login token for a logged-in visitor (OpenSpec `wordpress-plugin` D4).
 *
 * The widget fetches the token from admin-ajax.php with the visitor's own login cookie, so the
 * token is never in (cached) HTML. The format is Helder's headless format:
 * base64url(JSON {email, exp}) . base64url(HMAC-SHA256 with the customer login secret).
 * Guests get nothing: the action is only registered for logged-in users.
 *
 * @package Helder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Issues the login token.
 */
class Helder_Klanttoken {

	const ACTIE = 'helder_klanttoken';

	/** Seconds the token is valid; Helder accepts at most 600. */
	const GELDIG = 300;

	/**
	 * Registers the hooks. Deliberately no wp_ajax_nopriv_ action.
	 */
	public static function init() {
		add_action( 'wp_ajax_' . self::ACTIE, array( __CLASS__, 'antwoord' ) );
	}

	/**
	 * Base64url without padding.
	 *
	 * @param string $data Raw data.
	 * @return string
	 */
	private static function b64url( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- token encoding, not obfuscation.
	}

	/**
	 * A signed token for this email address.
	 *
	 * @param string   $geheim The customer login secret from Helder.
	 * @param string   $email  The visitor's email address.
	 * @param int|null $nu     Current time (for tests).
	 * @return string
	 */
	public static function maak( $geheim, $email, $nu = null ) {
		$nu      = null === $nu ? time() : (int) $nu;
		$payload = self::b64url(
			wp_json_encode(
				array(
					'email' => $email,
					'exp'   => $nu + self::GELDIG,
				)
			)
		);
		return $payload . '.' . self::b64url( hash_hmac( 'sha256', $payload, $geheim, true ) );
	}

	/**
	 * Answers the widget.
	 */
	public static function antwoord() {
		nocache_headers();
		$koppeling = Helder_Opslag::koppeling();
		$gebruiker = wp_get_current_user();
		$email     = $gebruiker instanceof WP_User ? (string) $gebruiker->user_email : '';
		if ( null === $koppeling || ! $koppeling['klanttoken'] || '' === $koppeling['klant_login_geheim'] || ! is_email( $email ) ) {
			wp_send_json( array( 'token' => null ) );
		}
		/**
		 * Filters whether this user may be passed on to Helder as a logged-in customer.
		 *
		 * @param bool    $toegestaan Default true.
		 * @param WP_User $gebruiker  The logged-in user.
		 */
		if ( ! apply_filters( 'helder_klanttoken_toegestaan', true, $gebruiker ) ) {
			wp_send_json( array( 'token' => null ) );
		}
		wp_send_json( array( 'token' => self::maak( $koppeling['klant_login_geheim'], strtolower( $email ) ) ) );
	}
}
