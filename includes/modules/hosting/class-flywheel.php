<?php
/**
 * Flywheel
 *
 * @package wld-site
 */

namespace WLDS\Module\Hosting;

use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Flywheel Class
 */
class Flywheel extends Modules {

	/**
	 * Init.
	 *
	 * @return void
	 */
	public function init() {
		// Fix WP Health Check Tests for sites on the Flywheel Platform.
		add_filter( 'site_status_tests', array( $this, 'wp_health_remove_tests' ), 10001, 1 );
	}

	/**
	 * WP Hooks
	 *
	 * @return void
	 */
	public function hooks() : void {

		// Are we running on the Flywheel Platform?
		if ( $this->is_flywheel_environment() ) {

			// Disable WP Rocket Pre-Loading.
			add_filter( 'wlds_wprocket_disable_preload', '__return_true' );

			// Remove Flywheel Script First.
			add_action( 'admin_enqueue_scripts', array( $this, 'whitelabel_admin_js_init' ), 1, 1 );

			// Replace with our own.
			add_action( 'admin_enqueue_scripts', array( $this, 'whitelabel_admin_js' ), 50, 1 );

			// Fix the build-in Auto Updates for Plugins/Themes as Flywheel blocks this by default.
			add_action( 'wp_update_plugins', array( $this, 'fix_wp_auto_updates' ), 20 );

			// Re-add the WordPress version check removed by the Flywheel Platform.
			if ( ! has_action( 'wp_version_check', 'wp_version_check' ) ) {
				add_action( 'wp_version_check', 'wp_version_check' );
			}
		}
	}

	/**
	 * Removes specific WP Health Tests.
	 *
	 * @param array $tests Array containing WP Health Tests.
	 *
	 * @return array
	 */
	public function wp_health_remove_tests( $tests ) {
		// Only remove tests in the Flywheel Environment.
		if ( $this->is_flywheel_environment() ) {
			// Tests to remove, as Flywheel disables automatic updates and blocks access to disk space usage.
			$tests_to_remove = array(
				'background_updates',
				'available_updates_disk_space',
				'scheduled_events',
				'persistent_object_cache',
				'wordpress_version',
			);

			// Loop through each test and remove it if it exists.
			foreach ( $tests_to_remove as $test_to_remove ) {
				if ( isset( $tests['direct'][ $test_to_remove ] ) ) {
					unset( $tests['direct'][ $test_to_remove ] );
				}
				if ( isset( $tests['async'][ $test_to_remove ] ) ) {
					unset( $tests['async'][ $test_to_remove ] );
				}
			}
		}
		return $tests;
	}

	/**
	 * Fixes the WP Auto Updater as Flywheel disables this
	 *
	 * @return void
	 */
	public function fix_wp_auto_updates() {
		if ( wp_doing_cron() && ! doing_action( 'wp_maybe_auto_update' ) ) {
			do_action( 'wp_maybe_auto_update' );
		}
	}

	/**
	 * Removes the Flywheel Hook
	 *
	 * @param string $hook WP Hook.
	 */
	public function whitelabel_admin_js_init( $hook ) {
		if ( 'update-core.php' === $hook ||
			'options-general.php' === $hook
		) {
			// Removes the Flywheel JS.
			remove_action( 'admin_enqueue_scripts', 'fw_enqueue_scripts' );
		}
	}

	/**
	 * The hook being added into the WP Admin based on which page
	 *
	 * @param string $hook WP Hook.
	 */
	public function whitelabel_admin_js( $hook ) : void {

		if ( 'update-core.php' === $hook ) {
				wp_register_script( 'wlds_flywheel_update_core', $this->get_url_js( 'wlds-admin-flywheel-core.min' ), array( 'jquery-core' ), $this->get_resource_version(), true );
				wp_enqueue_script( 'wlds_flywheel_update_core' );
				wp_localize_script(
					'wlds_flywheel_update_core',
					'wlds_f',
					array(
						'field_text_core' => _x( 'WordPress Core', 'Shown in WP Admin Updates page title', 'wld-site' ),
						'field_text'      => _x(
							'Your service provider automatically updates sites to the latest version of the WordPress core. Maintaining a current and up-to-date core is one of the most important things one can do to keep their site free from malicious activity and malware.',
							'Shown in WP Admin Updates page',
							'wld-site'
						),
					)
				);
		} elseif ( 'options-general.php' === $hook ) {
				wp_register_script( 'wlds_flywheel_options_general', $this->get_url_js( 'wlds-admin-general.min' ), array( 'jquery-core' ), $this->get_resource_version(), true );
				wp_enqueue_script( 'wlds_flywheel_options_general' );
				wp_localize_script(
					'wlds_flywheel_options_general',
					'wlds_f',
					array(
						'field_text' => _x( 'This field is managed by your service provider.', 'Shown underneath the Site URL field in Settings>General', 'wld-site' ),
					)
				);
		}
	}

	/**
	 * Checks if the current installation is running in a Flywheel Environment.
	 * Is the Server running some sort of Flywheel server config? (any version)
	 *
	 * @return bool
	 */
	protected function is_flywheel_environment() : bool {
		// phpcs:disable
		if (str_contains($_SERVER['SERVER_SOFTWARE'], 'Flywheel')) {
			// phpcs:enable
			return true;
		}
		return false;
	}
}
new Flywheel();
