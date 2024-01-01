<?php
/**
 * Cloudways
 *
 * @package wld-site
 */

namespace WLDS\Module\Hosting;

use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Module for Cloudways
 */
class Cloudways extends Modules {

	/**
	 * Init.
	 *
	 * @return void
	 */
	public function init() {
		// Fix WP Health Check Tests for sites on the Cloudways Platform.
		add_filter( 'site_status_tests', array( $this, 'wp_health_remove_tests' ), 10001, 1 );
	}

	/**
	 * Hooks to Init
	 *
	 * @return void
	 */
	public function hooks() {
		// Left blank.
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
	 * Checks if the current installation is running in a Cloudways Environment.
	 *
	 * @return bool
	 */
	protected function is_environment() : bool {
		// phpcs:disable
		if (str_contains($_SERVER['DOCUMENT_ROOT'], 'cloudwaysapps.com')) {
			// phpcs:enable
			return true;
		}
		return false;
	}
}
new Cloudways();
