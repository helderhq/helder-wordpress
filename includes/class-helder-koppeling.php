<?php
/**
 * Connecting and disconnecting (OpenSpec `wordpress-plugin` D3 in the Helder monorepo).
 *
 * 1. "Connect with Helder" sends the browser to Helder with the site address, the return URL and
 *    a random state.
 * 2. The owner picks a workspace in Helder; Helder sends the browser back with a one-time code.
 * 3. The plugin checks the state and exchanges the code server-to-server.
 *
 * @package Helder
 */

defined( 'ABSPATH' ) || exit;

/**
 * The connect flow and the daily status check.
 */
class Helder_Koppeling {

	const CRON = 'helder_controle';

	/**
	 * Registers the hooks.
	 */
	public static function init() {
		add_action( 'admin_post_helder_koppel', array( __CLASS__, 'start' ) );
		add_action( 'admin_post_helder_ontkoppel', array( __CLASS__, 'ontkoppel' ) );
		add_action( 'admin_init', array( __CLASS__, 'terug_van_helder' ) );
		add_action( self::CRON, array( __CLASS__, 'controleer' ) );
	}

	/**
	 * The plugin's settings page URL.
	 *
	 * @param array $extra Extra query arguments.
	 * @return string
	 */
	public static function beheer_url( array $extra = array() ) {
		return add_query_arg( array_merge( array( 'page' => 'helder' ), $extra ), admin_url( 'options-general.php' ) );
	}

	/**
	 * Transient key for the state of the current user.
	 *
	 * @return string
	 */
	private static function state_sleutel() {
		return 'helder_state_' . get_current_user_id();
	}

	/**
	 * Sends the browser to Helder to pick a workspace.
	 */
	public static function start() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'helder' ), 403 );
		}
		check_admin_referer( 'helder_koppel' );
		$state = wp_generate_password( 32, false, false );
		set_transient( self::state_sleutel(), $state, 10 * MINUTE_IN_SECONDS );
		$url = add_query_arg(
			array(
				'site'  => rawurlencode( home_url( '/' ) ),
				'terug' => rawurlencode( self::beheer_url() ),
				'state' => rawurlencode( $state ),
			),
			untrailingslashit( HELDER_APP_URL ) . '/koppelen/wordpress'
		);
		// Helder is an external host on purpose, so not wp_safe_redirect().
		wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}

	/**
	 * Handles the return from Helder on the settings page.
	 */
	public static function terug_van_helder() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- the random state replaces the nonce here.
		if ( ! isset( $_GET['page'], $_GET['helder_code'], $_GET['helder_state'] ) || 'helder' !== $_GET['page'] ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$code  = sanitize_text_field( wp_unslash( $_GET['helder_code'] ) );
		$state = sanitize_text_field( wp_unslash( $_GET['helder_state'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$verwacht = get_transient( self::state_sleutel() );
		delete_transient( self::state_sleutel() );
		if ( ! is_string( $verwacht ) || '' === $verwacht || ! hash_equals( $verwacht, $state ) ) {
			wp_safe_redirect( self::beheer_url( array( 'helder_melding' => 'state' ) ) );
			exit;
		}
		$koppeling = Helder_Api::wissel_code( $code );
		if ( is_wp_error( $koppeling ) ) {
			wp_safe_redirect( self::beheer_url( array( 'helder_melding' => 'mislukt' ) ) );
			exit;
		}
		Helder_Opslag::bewaar( $koppeling );
		self::plan_controle();
		wp_safe_redirect( self::beheer_url( array( 'helder_melding' => 'gekoppeld' ) ) );
		exit;
	}

	/**
	 * Disconnects the site, in Helder and here.
	 */
	public static function ontkoppel() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'helder' ), 403 );
		}
		check_admin_referer( 'helder_ontkoppel' );
		$koppeling = Helder_Opslag::koppeling();
		if ( null !== $koppeling ) {
			Helder_Api::ontkoppel( $koppeling );
		}
		Helder_WooCommerce::wis_keys();
		Helder_Opslag::wis();
		wp_safe_redirect( self::beheer_url( array( 'helder_melding' => 'ontkoppeld' ) ) );
		exit;
	}

	/**
	 * Daily: if the connection was removed in Helder, forget it here too, and remember whether
	 * Helder can read WooCommerce. When Helder cannot be reached, nothing changes.
	 */
	public static function controleer() {
		$koppeling = Helder_Opslag::koppeling();
		if ( null === $koppeling ) {
			return;
		}
		$status = Helder_Api::status( $koppeling );
		if ( null === $status ) {
			return;
		}
		if ( ! $status['gekoppeld'] ) {
			Helder_WooCommerce::wis_keys();
			Helder_Opslag::wis();
			return;
		}
		if ( $status['woocommerce'] !== $koppeling['woocommerce'] ) {
			Helder_Opslag::zet_woocommerce( $status['woocommerce'] );
		}
	}

	/**
	 * Schedules the daily check once.
	 */
	public static function plan_controle() {
		if ( ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', self::CRON );
		}
	}

	/**
	 * On activation.
	 */
	public static function activeer() {
		if ( null !== Helder_Opslag::koppeling() ) {
			self::plan_controle();
		}
	}

	/**
	 * On deactivation: stop the daily check. The connection stays, so reactivating needs no new link.
	 */
	public static function deactiveer() {
		wp_clear_scheduled_hook( self::CRON );
	}
}
