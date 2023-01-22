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
	 * WP Hooks
	 *
	 * @return void
	 */
	public function hooks() : void {
		if ( $this->is_flywheel_environment() ) {
			// Remove Flywheel Script First.
			add_action( 'admin_enqueue_scripts', array( $this, 'whitelabel_admin_js_init' ), 1, 1 );

			// Replace with our own.
			add_action( 'admin_enqueue_scripts', array( $this, 'whitelabel_admin_js' ), 50, 1 );
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
