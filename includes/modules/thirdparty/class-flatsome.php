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
	 * Init
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'site_status_tests', array( $this, 'remove_health_check_flatsome' ), 9 );
	}

	/**
	 * Removes an item from the health check.
	 *
	 * @param mixed $tests WP Health Tests.
	 *
	 * @return mixed
	 */
	public function remove_health_check_flatsome( $tests ) {
		// Remove Site Health Check.
		remove_filter( 'site_status_tests', 'flatsome_site_status_tests' );
		return $tests;
	}

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
