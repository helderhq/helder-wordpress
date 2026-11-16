<?php
/**
 * Puts the Helder loader on every front-end page once the site is connected
 * (OpenSpec `wordpress-plugin` D2). Without a connection the plugin outputs nothing and does not
 * contact Helder. Opening hours, page rules and language are managed in Helder.
 *
 * @package Helder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the loader.
 */
class Helder_Script {

	const HANDLE = 'helder';

	/**
	 * Registers the hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'zet' ) );
		add_filter( 'script_loader_tag', array( __CLASS__, 'sleutel_op_tag' ), 10, 2 );
	}

	/**
	 * Enqueues the loader and, when enabled, where the widget can fetch the login token.
	 */
	public static function zet() {
		$koppeling = Helder_Opslag::koppeling();
		if ( null === $koppeling ) {
			return;
		}
		// The loader is the Helder service itself, so it is loaded from Helder (not bundled).
		wp_enqueue_script(
			self::HANDLE,
			HELDER_WIDGET_URL,
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- versioned by Helder.
			array(
				'in_footer' => true,
				'strategy'  => 'async',
			)
		);
		if ( $koppeling['klanttoken'] ) {
			// The same for every visitor, so safe for page caches: who is logged in is never in the HTML.
			$token_url = add_query_arg( 'action', Helder_Klanttoken::ACTIE, admin_url( 'admin-ajax.php' ) );
			wp_add_inline_script(
				self::HANDLE,
				'window.HelderWidget=Object.assign(window.HelderWidget||{},' . wp_json_encode( array( 'tokenUrl' => $token_url ) ) . ');',
				'before'
			);
		}
	}

	/**
	 * Adds the public key to the loader's script tag.
	 *
	 * @param string $tag    The script tag.
	 * @param string $handle The script handle.
	 * @return string
	 */
	public static function sleutel_op_tag( $tag, $handle ) {
		if ( self::HANDLE !== $handle ) {
			return $tag;
		}
		$koppeling = Helder_Opslag::koppeling();
		if ( null === $koppeling ) {
			return $tag;
		}
		$attr = ' data-helder-sleutel="' . esc_attr( $koppeling['sleutel'] ) . '"';
		// Only the tag with the loader's src; the inline "before" script stays as it is.
		$src = preg_quote( esc_url( HELDER_WIDGET_URL ), '/' );
		return (string) preg_replace( '/<script(?=[^>]*\ssrc=["\']' . $src . ')/', '<script' . $attr, $tag, 1 );
	}
}
