<?php
/**
 * Settings page under Settings > Helder: connection status, "Connect with Helder",
 * "Pass on logged-in visitors" and "Disconnect". Everything else is managed in Helder.
 *
 * @package Helder
 */

defined( 'ABSPATH' ) || exit;

/**
 * The admin page.
 */
class Helder_Beheer {

	/**
	 * Registers the hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_helder_klanttoken', array( __CLASS__, 'bewaar_klanttoken' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( HELDER_PLUGIN_FILE ), array( __CLASS__, 'actielinks' ) );
	}

	/**
	 * Adds the page.
	 */
	public static function menu() {
		$hook = add_options_page( 'Helder', 'Helder', 'manage_options', 'helder', array( __CLASS__, 'pagina' ) );
		add_action( 'load-' . $hook, array( __CLASS__, 'bij_openen' ) );
	}

	/**
	 * "Settings" link on the plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public static function actielinks( $links ) {
		array_unshift( $links, '<a href="' . esc_url( Helder_Koppeling::beheer_url() ) . '">' . esc_html__( 'Settings', 'helder' ) . '</a>' );
		return $links;
	}

	/**
	 * When the page opens, check with Helder that the connection still exists.
	 */
	public static function bij_openen() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check.
		if ( ! isset( $_GET['helder_code'] ) ) {
			Helder_Koppeling::controleer();
		}
	}

	/**
	 * Saves "Pass on logged-in visitors".
	 */
	public static function bewaar_klanttoken() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'helder' ), 403 );
		}
		check_admin_referer( 'helder_klanttoken' );
		Helder_Opslag::zet_klanttoken( ! empty( $_POST['klanttoken'] ) );
		wp_safe_redirect( Helder_Koppeling::beheer_url( array( 'helder_melding' => 'bewaard' ) ) );
		exit;
	}

	/**
	 * A notice after an action.
	 */
	private static function melding() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		$melding = isset( $_GET['helder_melding'] ) ? sanitize_key( wp_unslash( $_GET['helder_melding'] ) ) : '';
		$teksten = array(
			'gekoppeld'  => array( 'success', __( 'Your site is connected. Helder chat now appears on your site.', 'helder' ) ),
			'ontkoppeld' => array( 'success', __( 'Your site is disconnected from Helder.', 'helder' ) ),
			'bewaard'    => array( 'success', __( 'Settings saved.', 'helder' ) ),
			'state'      => array( 'error', __( 'This link has expired. Click Connect with Helder again.', 'helder' ) ),
			'mislukt'    => array( 'error', __( 'Connecting did not work. Click Connect with Helder again.', 'helder' ) ),
		);
		if ( ! isset( $teksten[ $melding ] ) ) {
			return;
		}
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $teksten[ $melding ][0] ),
			esc_html( $teksten[ $melding ][1] )
		);
	}

	/**
	 * Renders the page.
	 */
	public static function pagina() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$koppeling = Helder_Opslag::koppeling();
		$https     = 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME );
		?>
		<div class="wrap">
			<h1>Helder</h1>
			<?php self::melding(); ?>
			<?php if ( null === $koppeling ) : ?>
				<p><?php esc_html_e( 'Connect your site with Helder to show Helder chat on every page. You choose your Helder workspace in the next step; if you do not have an account yet, you can create one there.', 'helder' ); ?></p>
				<?php if ( ! $https ) : ?>
					<div class="notice notice-warning inline"><p><?php esc_html_e( 'Your site does not use https. Helder can only connect sites on https.', 'helder' ); ?></p></div>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="helder_koppel" />
					<?php wp_nonce_field( 'helder_koppel' ); ?>
					<?php submit_button( __( 'Connect with Helder', 'helder' ), 'primary', 'submit', false ); ?>
				</form>
			<?php else : ?>
				<p>
					<?php
					printf(
						/* translators: %s: the site's host name */
						esc_html__( 'Connected. Helder chat appears on %s.', 'helder' ),
						'<strong>' . esc_html( $koppeling['host'] ) . '</strong>'
					);
					?>
				</p>
				<p>
					<?php
					printf(
						/* translators: %s: link to Helder */
						esc_html__( 'Opening hours, appearance, language and on which pages the chat appears are managed in %s.', 'helder' ),
						'<a href="' . esc_url( HELDER_APP_URL ) . '" target="_blank" rel="noopener">Helder</a>'
					);
					?>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="helder_klanttoken" />
					<?php wp_nonce_field( 'helder_klanttoken' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Logged-in visitors', 'helder' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="klanttoken" value="1" <?php checked( $koppeling['klanttoken'] ); ?> />
									<?php esc_html_e( 'Pass on who is logged in, so they do not have to verify themselves in the chat', 'helder' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'WordPress confirms the email address of new accounts by email. If people can create an account on your site without confirming their email address (for example in a shop), leave this off.', 'helder' ); ?></p>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Save', 'helder' ) ); ?>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="helder_ontkoppel" />
					<?php wp_nonce_field( 'helder_ontkoppel' ); ?>
					<?php submit_button( __( 'Disconnect', 'helder' ), 'delete', 'submit', false ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}
}
