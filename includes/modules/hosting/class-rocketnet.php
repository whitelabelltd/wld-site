<?php
/**
 * RocketNet
 *
 * @package wld-site
 */

namespace WLDS\Module\Hosting;

use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * RocketNet Class
 */
class RocketNet extends Modules {

	/**
	 * Init.
	 *
	 * @return void
	 */
	public function init() {
		if ( $this->is_environment() ) {
			// Disable WP Rocket Pre-Loading.
			add_filter( 'wlds_wprocket_disable_preload', '__return_true' );
		}

		// Fix WP Health Check Tests for sites on the Rocket.Net Platform.
		add_filter( 'site_status_tests', array( $this, 'wp_health_remove_tests' ), 10001, 1 );
	}

	/**
	 * Init Hook
	 *
	 * @return void
	 */
	public function hooks() {
		if ( $this->is_environment() ) {
			// Disable setting real IP, as the host already does this.
			add_filter( 'wlds_cloudflare_set_real_ip', '__return_false' );
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
		// Only remove tests in specific Environment.
		if ( $this->is_environment() ) {
			// Tests to remove.
			$tests_to_remove = array(
				'persistent_object_cache',
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
	 * Checks if the current installation is running in a Rocket.Net Environment.
	 *
	 * @return bool
	 */
	protected function is_environment() : bool {

		// Check for specific variables.
		if ( defined( 'CDN_SITE_TOKEN' ) && defined( 'CDN_SITE_ID' ) ) {
			return true;
		}

		// Fallback Check.
		// phpcs:disable
		if( isset( $_SERVER['SERVER_ADMIN'] ) ) {
			if (str_ends_with( $_SERVER['SERVER_ADMIN'], 'wpdns.site') ) {
				// phpcs:enable
				return true;
			}
		}
		return false;
	}
}
new RocketNet();
