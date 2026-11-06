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
	 * Set security headers: X-Frame-Options (clickjacking), X-Content-Type-Options and Referrer-Policy
	 *
	 * @param array $headers Headers.
	 */
	public function maybe_set_frame_option_header( $headers ) {

		// Safe defaults, never overriding a header that is already set.
		if ( ! isset( $headers['X-Content-Type-Options'] ) ) {
			$headers['X-Content-Type-Options'] = 'nosniff';
		}
		if ( ! isset( $headers['Referrer-Policy'] ) ) {
			$headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
		}

		// Allow omission of this header.
		if ( true === apply_filters( 'wlds_disable_x_frame_options', false ) ) {
			return $headers;
		}

		// Valid header values are `SAMEORIGIN` (allow iframe on same domain) | `DENY` (do not allow anywhere).
		$frame_option = apply_filters( 'wlds_x_frame_options', 'SAMEORIGIN' );
		$frame_option = is_string( $frame_option ) ? strtoupper( trim( $frame_option, " \t\n\r\0\x0B" ) ) : '';
		if ( ! in_array( $frame_option, array( 'SAMEORIGIN', 'DENY' ), true ) ) {
			$frame_option = 'SAMEORIGIN';
		}
		$headers['X-Frame-Options'] = $frame_option;
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
