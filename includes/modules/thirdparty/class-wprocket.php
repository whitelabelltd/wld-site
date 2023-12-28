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
		$this->maybe_disable_preload();
	}

	/**
	 * Init Hooks
	 *
	 * @inheritDoc
	 */
	public function hooks() {
		// Removes the Powered By HTTP Header.
		add_filter( 'rocket_htaccess_files_match', array( $this, 'remove_powered_by_header' ) );
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
	 * Disables the Pre-Load Option for WP-Rocket
	 * source https://docs.wp-rocket.me/article/1564-list-of-pre-get-rocket-option-filters.
	 *
	 * @return void
	 */
	public function maybe_disable_preload() {
		if ( apply_filters( 'wlds_wprocket_disable_preload', false ) ) {
			add_filter( 'pre_get_rocket_option_manual_preload', '__return_zero' );
			add_filter( 'pre_get_rocket_option_preload_links', '__return_zero' );
		}
	}

	/**
	 * Removes the Powered By HTTP Header
	 *
	 * @param string $rules Rules.
	 *
	 * @return string
	 */
	public function remove_powered_by_header( $rules ) {
		return str_replace( 'Header set X-Powered-By "WP Rocket/' . WP_ROCKET_VERSION . '"' . PHP_EOL, '', $rules );
	}
}
new WPRocket();
