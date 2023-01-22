<?php
/**
 * Flatsome
 *
 * @package wld-site
 */

namespace WLDS\Module\ThirdParty;

use WLDS\Modules;

/**
 * Flatsome
 */
class Flatsome extends Modules {

	/**
	 * Init Hooks.
	 *
	 * @return void
	 */
	public function hooks() {

		// Allow filter to disable this action.
		if ( apply_filters( 'wlds_flatsome_admin_bar_remove', true ) ) {
			// Removes the Flatsome Admin Bar Menu Items.
			add_filter( 'wlds_admin_bar_items', array( $this, 'remove_admin_bar_items' ), 10, 1 );
		}
	}

	/**
	 * Removes the Flatsome Admin Bar Menu Items
	 *
	 * @param array $items_to_remove Admin Bar items to remove.
	 * @return array
	 */
	public function remove_admin_bar_items( $items_to_remove = array() ) {
		$items_to_remove[] = 'flatsome-activate';
		$items_to_remove[] = 'flatsome_panel';
		return $items_to_remove;
	}
}
new Flatsome();
