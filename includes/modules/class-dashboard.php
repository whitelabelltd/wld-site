<?php
/**
 * Dashboard
 *
 * @package wld-site
 */

namespace WLDS\Module;

use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Dashboard
 */
class Dashboard extends Modules {

	/**
	 * Init
	 *
	 * @inheritDoc
	 */
	public function hooks() : void {
		add_action( 'admin_menu', array( $this, 'remove_widgets' ) );
	}

	/**
	 * Removes WP Widgets
	 *
	 * @return void
	 */
	public function remove_widgets() : void {
		// Remove WordPress Events and News.
		remove_meta_box( 'dashboard_primary', 'dashboard', 'core' );
	}
}
new Dashboard();
