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
}
new Spinupwp();
