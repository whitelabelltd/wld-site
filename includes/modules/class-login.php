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
	 * @param string $error Error Message.
	 *
	 * @return string
	 */
	public function hide_login_errors( $error ) {
		global $errors;

		// Set Default Error Text.
		/* translators: Login, invalid details entered */
		$wld_site_error = sprintf( '<strong>%s</strong>&nbsp;%s', __( 'ERROR:', 'wld-site' ), __( 'Invalid Login Details', 'wld-site' ) );

		// Bail early.
		if ( ! $errors ) {
			return $wld_site_error;
		}

		// Grab Error Codes.
		$err_codes = $errors->get_error_codes();

		// Invalid username, email or password, or lost-password lookup failure.
		if ( array_intersect( array( 'invalid_username', 'incorrect_password', 'invalid_email', 'invalidcombo' ), $err_codes ) ) {
			$error = $wld_site_error;
		}

		// Other Error, Return that instead.
		return $error;
	}
}
new Login();
