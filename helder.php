<?php
/**
 * Plugin Name:       Helder
 * Plugin URI:        https://helderhq.com/koppeling-website
 * Description:       Adds Helder chat to your site with one click, lets logged-in visitors chat without verifying again and, with WooCommerce, lets the chat answer questions about orders.
 * Version:           0.2.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Helder
 * Author URI:        https://helderhq.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       helder
 * Domain Path:       /languages
 *
 * @package Helder
 */

defined( 'ABSPATH' ) || exit;

define( 'HELDER_VERSION', '0.2.0' );
define( 'HELDER_PLUGIN_FILE', __FILE__ );

if ( ! defined( 'HELDER_APP_URL' ) ) {
	// Override in wp-config.php for testing against another Helder environment.
	define( 'HELDER_APP_URL', 'https://app.helderhq.com' );
}
if ( ! defined( 'HELDER_WIDGET_URL' ) ) {
	define( 'HELDER_WIDGET_URL', 'https://widget.helderhq.com/helder.js' );
}

require_once __DIR__ . '/includes/class-helder-opslag.php';
require_once __DIR__ . '/includes/class-helder-api.php';
require_once __DIR__ . '/includes/class-helder-koppeling.php';
require_once __DIR__ . '/includes/class-helder-script.php';
require_once __DIR__ . '/includes/class-helder-klanttoken.php';
require_once __DIR__ . '/includes/class-helder-beheer.php';
require_once __DIR__ . '/includes/class-helder-woocommerce.php';

add_action(
	'init',
	static function () {
		load_plugin_textdomain( 'helder', false, dirname( plugin_basename( HELDER_PLUGIN_FILE ) ) . '/languages' );
	}
);

Helder_Koppeling::init();
Helder_Script::init();
Helder_Klanttoken::init();
Helder_Beheer::init();
Helder_WooCommerce::init();

register_activation_hook( __FILE__, array( 'Helder_Koppeling', 'activeer' ) );
register_deactivation_hook( __FILE__, array( 'Helder_Koppeling', 'deactiveer' ) );
