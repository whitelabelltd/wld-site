<?php
/**
 * ManageWP
 *
 * @package wld-site
 */

namespace WLDS\Module\ThirdParty;

use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ManageWP Class
 */
class ManageWP extends Modules {

	/**
	 * Init Hooks.
	 *
	 * @inheritDoc
	 */
	public function hooks() {
		if ( ! $this->is_agency_member() ) {
			add_action( 'admin_init', array( $this, 'hide_notices' ), 20 );
		}
	}

	/**
	 * Hides the ManageWP Notice if it exists
	 *
	 * @return void
	 */
	public function hide_notices() : void {
		if ( function_exists( 'mwp_core' ) ) {
			$class = \mwp_core();
			remove_action( 'admin_notices', array( $class, 'admin_notice' ) );
		}
	}
}
new ManageWP();
