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
	 * Hooks to Init
	 *
	 * @return void
	 */
	public function hooks() {

	}

	/**
	 * Checks if the current installation is running in a Cloudways Environment.
	 *
	 * @return bool
	 */
	protected function is_cloudways_environment() : bool {
		// phpcs:disable
		if (str_contains($_SERVER['DOCUMENT_ROOT'], 'cloudwaysapps.com')) {
			// phpcs:enable
			return true;
		}
		return false;
	}
}
new Cloudways();
