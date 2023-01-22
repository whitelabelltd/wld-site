<?php
/**
 * Login
 *
 * @package wld-site
 */

namespace WLDS\Module;

use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Login Class
 */
class Login extends Modules {

	/**
	 * Constructor
	 */
	public function hooks() {
		// Hides Login Errors.
		add_filter( 'login_errors', array( $this, 'hide_login_errors' ) );
	}

	/**
	 * Replaces login error message with a generic one
	 *
	 * @return string
	 */
	public function hide_login_errors() {
		/* translators: Login, invalid details entered */
		return esc_html_x( 'ERROR: Invalid Login Details', 'WP Login Error Message', 'wld-site' );
	}
}
new Login();
