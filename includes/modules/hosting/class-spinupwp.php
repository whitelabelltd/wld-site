<?php
/**
 * SpinupWP
 *
 * @package wld-site
 */

namespace WLDS\Module\Hosting;

use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SpinupWP Class
 */
class Spinupwp extends Modules {

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
	 * Init Hook
	 *
	 * @return void
	 */
	public function hooks() {

	}

	/**
	 * Checks if the current installation is running in a SpinupWP Environment.
	 *
	 * @return bool
	 */
	protected function is_spinup_environment() : bool {
		// phpcs:disable
		if (isset($_SERVER['SPINUPWP_SITE']) &&
		    isset($_SERVER['SPINUPWP_LOG_PATH']) &&
		    str_contains($_SERVER['SPINUPWP_LOG_PATH'],'sites/')
		) {
			// phpcs:enable
			return true;
		}
		return false;
	}

	/**
	 * Removes specific WP Health Tests.
	 *
	 * @param array $tests Array containing WP Health Tests.
	 *
	 * @return array
	 */
	public function wp_health_remove_tests( $tests ) {
		// Only remove tests in the SpinupWP Environment.
		if ( $this->is_spinup_environment() ) {
			// Tests to remove, as SpinupWP locks access to disk space usage.
			$tests_to_remove = array(
				'available_updates_disk_space',
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
}
new Spinupwp();
