<?php
/**
 * WP Rocket
 *
 * @package wld-site
 */

namespace WLDS\Module\ThirdParty;

use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WP Rocket Class
 */
class WPRocket extends Modules {

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function init() : void {
		$this->whitelabel_wp_rocket();
	}

	/**
	 * Whitelabel WP Rocket
	 *
	 * @return void
	 */
	public function whitelabel_wp_rocket() {
		if ( ! defined( 'WP_ROCKET_WHITE_LABEL_FOOTPRINT' ) ) {
			define( 'WP_ROCKET_WHITE_LABEL_FOOTPRINT', true );
		}
	}

	/**
	 * Init Hooks
	 *
	 * @inheritDoc
	 */
	public function hooks() {
		// Left blank.
	}
}
new WPRocket();
