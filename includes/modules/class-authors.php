<?php
/**
 * Author customizations
 *
 * @package wld-site
 */

namespace WLDS\Module;

use WLDS\Modules;
use function wp_safe_redirect;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Authors class
 */
class Authors extends Modules {

	/**
	 * Setup module
	 */
	public function hooks() {
		if ( apply_filters( 'wlds_author_archive_disable', true ) ) {
			add_action( 'template_redirect', array( $this, 'maybe_disable_author_archive' ) );
		}
	}

	/**
	 * Check to see if author archive page should be disabled for agency user accounts
	 */
	public function maybe_disable_author_archive() {

		// Bail Early.
		if ( ! is_author() || is_admin() ) {
			return;
		}

		// Load Author.
		$author = get_queried_object();

		// Redirect if the author is an agency member.
		if ( $author && $this->is_agency_member( $author->ID ) ) {

			// Redirect to homepage.
			wp_safe_redirect( home_url(), '301', 'WLD Site' );
			exit();
		}
	}
}
new Authors();
