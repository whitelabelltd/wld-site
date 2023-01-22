<?php
/**
 * Header customisations
 *
 * @package wld-site
 */

namespace WLDS\Module;

use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Headers class
 */
class Headers extends Modules {

	/**
	 * Setup module
	 */
	public function init() {
		add_action( 'wp_headers', array( $this, 'maybe_set_frame_option_header' ), 99, 1 );
	}

	/**
	 * Set the X-Frame-Options header to 'SAMEORIGIN' to prevent clickjacking attacks
	 *
	 * @param string $headers Headers.
	 */
	public function maybe_set_frame_option_header( $headers ) {

		// Allow omission of this header.
		if ( true === apply_filters( 'wlds_disable_x_frame_options', false ) ) {
			return $headers;
		}

		// Valid header values are `SAMEORIGIN` (allow iframe on same domain) | `DENY` (do not allow anywhere).
		$headers['X-Frame-Options'] = apply_filters( 'wlds_x_frame_options', 'SAMEORIGIN' );
		return $headers;
	}

	/**
	 * Init Hooks
	 *
	 * @inheritDoc
	 */
	public function hooks() {
		// Left Blank.
	}
}
new Headers();
