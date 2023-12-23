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
		if ( $this->is_rocketnet_environment() ) {
			// Disable WP Rocket Pre-Loading.
			add_filter( 'wlds_wprocket_disable_preload', '__return_true' );
		}
	}

	/**
	 * Init Hook
	 *
	 * @return void
	 */
	public function hooks() {
		if ( $this->is_rocketnet_environment() ) {
			// Disable setting real IP, as the host already does this.
			add_filter( 'wlds_cloudflare_set_real_ip', '__return_false' );
		}
	}

	/**
	 * Checks if the current installation is running in a Rocket.Net Environment.
	 *
	 * @return bool
	 */
	protected function is_rocketnet_environment() : bool {

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
