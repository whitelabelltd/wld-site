<?php
/**
 * WooCommerce
 *
 * @package wld-site
 */

namespace WLDS\Module\ThirdParty;

use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WooCommerce Class
 */
class WooCommerce extends Modules {

	/**
	 * WP Hooks
	 *
	 * @return void
	 */
	public function hooks() : void {
		// Disable Marketplace Suggestions.
		add_filter( 'woocommerce_allow_marketplace_suggestions', '__return_false' );
	}
}
new WooCommerce();
