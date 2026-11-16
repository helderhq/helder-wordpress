<?php
/**
 * WooCommerce module (OpenSpec `woocommerce-module` in the Helder monorepo).
 *
 * Gives Helder read access to orders through WooCommerce's own approval screen (wc-auth):
 * 1. The plugin asks Helder for a short-lived signed state for this connection.
 * 2. The browser goes to WooCommerce's approval screen with that state as `user_id`.
 * 3. After approval, WooCommerce sends the read-only keys straight to Helder. The plugin never
 *    sees or stores them.
 *
 * Only does anything when WooCommerce is active.
 *
 * @package Helder
 */

defined( 'ABSPATH' ) || exit;

/**
 * The WooCommerce access flow.
 */
class Helder_WooCommerce {

	/**
	 * WooCommerce names the keys "<app name> - API (<date>)".
	 */
	const KEY_OMSCHRIJVING = 'Helder - API (';

	/**
	 * Registers the hooks.
	 */
	public static function init() {
		add_action( 'admin_post_helder_woo_toegang', array( __CLASS__, 'start' ) );
		add_action( 'admin_post_helder_woo_intrekken', array( __CLASS__, 'intrekken' ) );
		add_action( 'admin_init', array( __CLASS__, 'terug_van_woocommerce' ) );
	}

	/**
	 * Is WooCommerce active?
	 *
	 * @return bool
	 */
	public static function actief() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * May the current user give access? WooCommerce's approval screen needs manage_woocommerce.
	 *
	 * @return bool
	 */
	private static function mag() {
		return current_user_can( 'manage_options' ) && current_user_can( 'manage_woocommerce' );
	}

	/**
	 * The URL of WooCommerce's approval screen, with or without pretty permalinks.
	 *
	 * @param array $args Query arguments for wc-auth.
	 * @return string
	 */
	public static function goedkeuring_url( array $args ) {
		if ( get_option( 'permalink_structure' ) ) {
			return add_query_arg( $args, home_url( '/wc-auth/v1/authorize' ) );
		}
		$args['wc-auth-version'] = 1;
		$args['wc-auth-route']   = 'authorize';
		return add_query_arg( $args, home_url( '/' ) );
	}

	/**
	 * Sends the browser to WooCommerce's approval screen.
	 */
	public static function start() {
		if ( ! self::mag() || ! self::actief() ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'helder' ), 403 );
		}
		check_admin_referer( 'helder_woo_toegang' );
		$koppeling = Helder_Opslag::koppeling();
		if ( null === $koppeling ) {
			wp_safe_redirect( Helder_Koppeling::beheer_url() );
			exit;
		}
		$state = Helder_Api::woo_state( $koppeling, home_url( '/' ) );
		if ( is_wp_error( $state ) ) {
			wp_safe_redirect( Helder_Koppeling::beheer_url( array( 'helder_melding' => 'woo_mislukt' ) ) );
			exit;
		}
		$url = self::goedkeuring_url(
			array(
				'app_name'     => 'Helder',
				'scope'        => 'read',
				'user_id'      => rawurlencode( $state ),
				'return_url'   => rawurlencode( Helder_Koppeling::beheer_url( array( 'helder_woo' => 'terug' ) ) ),
				'callback_url' => rawurlencode( untrailingslashit( HELDER_APP_URL ) . '/api/wordpress/woocommerce' ),
			)
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Back from WooCommerce's approval screen: ask Helder whether the keys arrived.
	 */
	public static function terug_van_woocommerce() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display only; Helder decides.
		if ( ! isset( $_GET['page'], $_GET['helder_woo'] ) || 'helder' !== $_GET['page'] || 'terug' !== $_GET['helder_woo'] ) {
			return;
		}
		$gelukt = isset( $_GET['success'] ) && '1' === $_GET['success'];
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( ! self::mag() ) {
			return;
		}
		$koppeling = Helder_Opslag::koppeling();
		$status    = null === $koppeling ? null : Helder_Api::status( $koppeling );
		if ( null !== $status ) {
			Helder_Opslag::zet_woocommerce( $status['woocommerce'] );
		}
		$actief = null !== $status && $status['woocommerce'];
		wp_safe_redirect( Helder_Koppeling::beheer_url( array( 'helder_melding' => $gelukt && $actief ? 'woo_gekoppeld' : 'woo_mislukt' ) ) );
		exit;
	}

	/**
	 * Withdraws Helder's access to WooCommerce. The chat stays connected.
	 */
	public static function intrekken() {
		if ( ! self::mag() ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'helder' ), 403 );
		}
		check_admin_referer( 'helder_woo_intrekken' );
		$koppeling = Helder_Opslag::koppeling();
		if ( null !== $koppeling ) {
			Helder_Api::woo_intrekken( $koppeling );
			Helder_Opslag::zet_woocommerce( false );
		}
		self::wis_keys();
		wp_safe_redirect( Helder_Koppeling::beheer_url( array( 'helder_melding' => 'woo_ingetrokken' ) ) );
		exit;
	}

	/**
	 * Deletes the keys WooCommerce made for Helder. Safe without WooCommerce.
	 */
	public static function wis_keys() {
		global $wpdb;
		$tabel = $wpdb->prefix . 'woocommerce_api_keys';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery -- WooCommerce has no API to delete keys; it uses $wpdb too.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $tabel ) ) ) !== $tabel ) {
			return;
		}
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE description LIKE %s',
				$tabel,
				$wpdb->esc_like( self::KEY_OMSCHRIJVING ) . '%'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * The WooCommerce section on the settings page.
	 *
	 * @param array $koppeling The stored connection.
	 */
	public static function sectie( array $koppeling ) {
		if ( ! self::actief() ) {
			return;
		}
		?>
		<h2><?php esc_html_e( 'WooCommerce', 'helder' ); ?></h2>
		<?php if ( $koppeling['woocommerce'] ) : ?>
			<p><?php esc_html_e( 'Helder can read your orders, customers and products, so the chat can answer questions about orders.', 'helder' ); ?></p>
			<?php if ( self::mag() ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="helder_woo_intrekken" />
					<?php wp_nonce_field( 'helder_woo_intrekken' ); ?>
					<?php submit_button( __( 'Withdraw access', 'helder' ), 'secondary', 'submit', false ); ?>
				</form>
			<?php endif; ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Give Helder read-only access to your orders, customers and products, so the chat can tell customers where their order is. WooCommerce asks you to approve this.', 'helder' ); ?></p>
			<?php if ( self::mag() ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="helder_woo_toegang" />
					<?php wp_nonce_field( 'helder_woo_toegang' ); ?>
					<?php submit_button( __( 'Give Helder access to orders', 'helder' ), 'primary', 'submit', false ); ?>
				</form>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Only a shop manager can give access.', 'helder' ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
		<?php
	}
}
