<?php
/**
 * Plugin Name:       WLD Site Platform
 * Plugin URI:        https://whitelabel.ltd
 * Description:       WLD Site configures WordPress with additional security and features
 * Version:           1.0.9
 * Author:            Whitelabel Digital
 * Author URI:        https://whitelabel.ltd
 * Requires at least: 6.3
 * Requires PHP:      8.0
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wld-site
 * Domain Path:       /languages/
 * Plugin Folder:     wld-site
 *
 * @package           wld-site
 */

namespace WLDS;

use WLDS\Helper\Tools;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

define( 'WLDS_VERSION', '1.0.9' );
define( 'WLDS_DIR', __DIR__ );
define( 'WLDS_FILE', __FILE__ );
if ( ! defined( 'WLDS_UPDATER_URL' ) ) {
	// Set Auto Updater Github URL.
	define( 'WLDS_UPDATER_URL', 'https://github.com/whitelabelltd/wld-site/' );
}

/**
 * The core plugin class
 */
require_once plugin_dir_path( WLDS_FILE ) . 'includes/class-core.php';

/**
 * Gets the main Class Instance
 *
 * @return Core
 */
function init_wlds() {

	// globals.
	global $wlds;

	// initialise.
	if ( ! isset( $wlds ) ) {
		$wlds = new Core();
		$wlds->init();
	}

	// return.
	return $wlds;
}

// Initialise Plugin.
init_wlds();
register_activation_hook( __FILE__, 'WLDS\_wlds_activate' );
register_deactivation_hook( __FILE__, 'WLDS\_wlds_deactivate' );

/**
 * Runs upon plugin de-activation
 *
 * @return void
 */
function _wlds_deactivate() : void {

	// Holds the Cron Hooks to remove.
	$cron_hooks = array(
		// Cloudflare Cron Remove.
		'wlds_update_cloudflare_ips',
		// Auto Updater.
		'puc_cron_check_updates-wld-site',
	);

	// Loop through each hook and remove from Cron.
	foreach ( $cron_hooks as $cron_hook ) {
		if ( wp_next_scheduled( $cron_hook ) ) {
			wp_unschedule_hook( $cron_hook );
		}
	}
}

/**
 * Runs upon activation
 *
 * @return void
 */
function _wlds_activate() : void {
	// Load Extra Settings if Found.
	$file = plugin_dir_path( WLDS_FILE ) . 'client.json';
	if ( file_exists( $file ) ) {

		// Load File.
		// phpcs:disable
		$file_data = json_decode( file_get_contents( $file ), 1 );
		// phpcs:enable

		// Set the options.
		Tools::set_json_options( $file_data );

		// Remove the file.
		unlink( $file );
	}
}

/**
 * Updater
 */
function _wlds_updater() : void {
	// Only Run when not in a local environment.
	if ( ! Tools::is_local_env() ) {
		// Init Updater.
		$updater = PucFactory::buildUpdateChecker(
			WLDS_UPDATER_URL,
			WLDS_FILE,
			'wld-site'
		);
		// Look for Releases Only.
		$updater->getVcsApi()->enableReleaseAssets();
	}
}
_wlds_updater();
