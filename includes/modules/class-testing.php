<?php
/**
 * Testing Module
 *
 * @package wld-site
 */

// phpcs:disable

namespace WLDS\Module;

use WLDS\Helper\Agency;

class Testing {

	/**
	 * Test Code to Run
	 * @return void
	 */
	public function run() {




	}

	/**
	 * Testing constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * Load Menu Items
	 */
	public function admin_menu() {

		$capability = 'manage_options';
		$slug = 'wlld_test_menu';

		// Main Testing Menu Item
		add_menu_page(
			__( 'Testing', 'wld-site' ),
			'Testing',
			$capability,
			$slug,
			array($this, 'run'),
			'dashicons-code-standards',
			18
		);

	}
}
new Testing();
