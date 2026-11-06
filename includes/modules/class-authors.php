<?php
/**
 * Author customizations
 *
 * @package wld-site
 */

namespace WLDS\Module;

use WLDS\Helper\Agency;
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
			// Priority 9 so this runs before redirect_canonical (10), which would leak /author/<slug>/.
			add_action( 'template_redirect', array( $this, 'maybe_disable_author_archive' ), 9 );
			add_filter( 'wp_sitemaps_users_query_args', array( $this, 'exclude_agency_users_from_sitemap' ) );
		}
	}

	/**
	 * Excludes agency member accounts from the users sitemap
	 *
	 * @param array $args WP_User_Query arguments.
	 * @return array
	 */
	public function exclude_agency_users_from_sitemap( $args ): array {
		$args    = (array) $args;
		$domains = Agency::get_agency_domains();

		if ( empty( $domains ) || ! is_array( $domains ) ) {
			return $args;
		}

		$exclude = isset( $args['exclude'] ) ? (array) $args['exclude'] : array();

		foreach ( $domains as $domain ) {
			$ids = get_users(
				array(
					'search'         => '*@' . $domain,
					'search_columns' => array( 'user_email' ),
					'fields'         => 'ID',
				)
			);
			foreach ( $ids as $id ) {
				$exclude[] = (int) $id;
			}
		}

		$args['exclude'] = array_values( array_unique( $exclude ) );
		return $args;
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
