<?php
/**
 * Core
 *
 * @package wld-site
 */

namespace WLDS;

use WLDS\Helper\Tools;

/**
 * Core Class
 */
class Core {

	/**
	 * Holds the Plugin Root file path
	 *
	 * @var string file of the plugin.
	 */
	protected $file;

	/**
	 * Transient name
	 *
	 * @var string new version transient name.
	 */
	protected $after_upgrade_transient = 'wlds_is_new_version';

	/**
	 * Load Items
	 *
	 * @return void
	 */
	protected function load() {
		// Composer Load.
		$this->load_composer();

		// Helpers.
		$this->load_helpers(
			array(
				'agency',
				'iputil',
				'tools',
			)
		);

		// Modules.
		$this->load_modules(
			array(
				'admin',
				'api',
				'authors',
				'dashboard',
				'email',
				'headers',
				'login',
				'options',
				'security',
				'speed',
				'support',

				'testing{local}',
			)
		);

		// Hosting Platform Modules.
		$this->load_modules_hosting(
			array(
				'flywheel',
			)
		);
		// spinupwp, cloudways.

		// Third Party Modules.
		$this->load_modules_third_party(
			array(
				'cloudflare',
				'flatsome',
				'googletag',
				'managewp',
				'woocommerce',
				'wprocket',
				'yoast',
			)
		);
	}

	/**
	 * Initialises the Core Class
	 *
	 * @return void
	 */
	public function init() {
		// Set the Core File.
		$this->file = WLDS_FILE;

		// After Update.
		add_action( 'upgrader_process_complete', array( $this, 'upgrade_completed' ), 10, 2 );

		// Load Items.
		$this->load();

		// Run any Post Upgrade Actions.
		$this->upgrade_completed_run();

		// Load Text Domain.
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Loads the Text Domain
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'wld-site', false, dirname( plugin_basename( WLDS_FILE ) ) . '/languages' );
	}

	/**
	 * Loads the Autoload for Composer
	 */
	protected function load_composer() {
		$path = plugin_dir_path( $this->file ) . 'vendor/autoload.php';
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}

	/**
	 * Loads any needed Helpers
	 *
	 * @param array $helpers helper names.
	 */
	protected function load_helpers( $helpers = array() ) {
		$path = plugin_dir_path( $this->file ) . 'includes/helpers/';
		foreach ( $helpers as $helper ) {
			$helper = $this->remove_test_item( $helper );
			$file   = trailingslashit( $path ) . basename( 'class-' . $helper ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}

	/**
	 * Loads any needed Modules
	 *
	 * @param array  $modules module names.
	 * @param string $sub_folder sub-folder name.
	 */
	protected function load_modules( $modules = array(), $sub_folder = '' ) {
		$path = plugin_dir_path( $this->file ) . 'includes/modules/';
		if ( $sub_folder ) {
			$path = $path . basename( $sub_folder );
		} else {
			// Load Core.
			$path_core = plugin_dir_path( $this->file ) . 'includes/class-modules.php';
			if ( file_exists( $path_core ) ) {
				require_once $path_core;
			}
		}
		foreach ( $modules as $module ) {
			$module = $this->remove_test_item( $module );
			$file   = trailingslashit( $path ) . basename( 'class-' . $module ) . '.php';
			if ( $module && file_exists( $file ) ) {
				require_once $file;
			}
		}
	}

	/**
	 * Loads any needed hosting Modules
	 *
	 * @param array $hosting_modules hosting module names.
	 */
	protected function load_modules_hosting( $hosting_modules = array() ) {
		$this->load_modules( $hosting_modules, 'hosting' );
	}

	/**
	 * Loads any needed third-party Modules
	 *
	 * @param array $thirdparty_modules third-party module names.
	 */
	protected function load_modules_third_party( $thirdparty_modules = array() ) {
		$this->load_modules( $thirdparty_modules, 'thirdparty' );
	}

	/**
	 * Is the item a test item, and we are running in a test environment, return it. Returns blank otherwise
	 *
	 * @param string $item item name.
	 * @return string
	 */
	protected function remove_test_item( $item = '' ) {

		$tag = '{local}';
		if ( str_contains( $item, $tag ) ) {
			if ( Tools::is_local_env() ) {
				return str_replace( $tag, '', $item );
			} else {
				return '';
			}
		}
		return $item;
	}

	/**
	 * After the plugin has been updated, set a transient to run after-upgrade code
	 *
	 * @param \WP_Upgrader $upgrader_object WP_Upgrader instance.
	 * @param array        $options Array of bulk item update data.
	 */
	public function upgrade_completed( $upgrader_object, $options ) {
		// The path to our plugin's main file.
		$our_plugin = $this->file;
		// If an update has taken place and the updated type is plugins and the plugins element exists.
		if ( 'update' === $options['action'] &&
			'plugin' === $options['type'] &&
			isset( $options['plugins'] )
		) {
			// Iterate through the plugins being updated and check if ours is there.
			foreach ( $options['plugins'] as $plugin ) {
				if ( $plugin === $our_plugin ) {
					// Set the transient that will run next time WP Loads.
					set_transient( $this->after_upgrade_transient, true, DAY_IN_SECONDS );
				}
			}
		}
	}

	/**
	 * Runs after the plugin does an update
	 */
	public function upgrade_completed_run() {
		if ( get_transient( $this->after_upgrade_transient ) ) {

			// Remove Transient.
			delete_transient( $this->after_upgrade_transient );

			// Reset Permalinks.
			flush_rewrite_rules();

			// Clear Caches
			// @todo.
		}
	}
}
