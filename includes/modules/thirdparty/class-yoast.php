<?php
/**
 * Yoast SEO
 *
 * @package wld-site
 */

namespace WLDS\Module\ThirdParty;

use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Yoast SEO Class
 */
class Yoast extends Modules {

	/**
	 * Disables HTML comments from Yoast
	 *
	 * @return void
	 */
	public function hooks() {
		// Remove Yoast HTML Comments from Front-End.
		add_filter( 'wpseo_debug_markers', '__return_false' );

		// Allow filter to disable this action.
		if ( apply_filters( 'wlds_yoast_admin_bar_remove', true ) ) {
			// Removes the Yoast Admin Bar Menu Item.
			add_filter( 'wlds_admin_bar_items', array( $this, 'remove_admin_bar_item' ), 10, 1 );
		}
	}

	/**
	 * Removes the Yoast Admin Bar Menu Item
	 *
	 * @param array $items_to_remove Admin bar items to remove.
	 * @return array
	 */
	public function remove_admin_bar_item( $items_to_remove = array() ) {
		$items_to_remove[] = 'wpseo-menu';
		return $items_to_remove;
	}
}
new Yoast();
