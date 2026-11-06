<?php
/**
 * Tests for login and author enumeration hardening.
 *
 * Run from the Local site shell:
 * cd wp-content/plugins/wld-site && wp eval-file test/test-enumeration.php
 *
 * @package wld-site
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

require __DIR__ . '/bootstrap.php';

$users     = wlds_test_users();
$member_id = $users['member'];
$client_id = $users['client'];

$login_error = static function ( $code ) {
	$had      = array_key_exists( 'errors', $GLOBALS );
	$previous = $had ? $GLOBALS['errors'] : null;
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$GLOBALS['errors'] = new WP_Error( $code, 'Original ' . $code );
	try {
		return apply_filters( 'login_errors', 'original' );
	} finally {
		if ( $had ) {
			$GLOBALS['errors'] = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} else {
			unset( $GLOBALS['errors'] );
		}
	}
};

try {
	$baseline = $login_error( 'incorrect_password' );
	wlds_test_assert( 'original' !== $baseline, 'login: incorrect_password masked' );
	wlds_test_assert( $baseline === $login_error( 'invalid_username' ), 'login: invalid_username masked' );
	wlds_test_assert( $baseline === $login_error( 'invalid_email' ), 'login: invalid_email masked' );
	wlds_test_assert( $baseline === $login_error( 'invalidcombo' ), 'login: invalidcombo masked' );
	wlds_test_assert( 'original' === $login_error( 'some_other_error' ), 'login: other errors untouched' );

	$authors_priority = false;
	global $wp_filter;
	if ( isset( $wp_filter['template_redirect'] ) ) {
		foreach ( $wp_filter['template_redirect']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$fn = $callback['function'];
				if ( is_array( $fn ) && $fn[0] instanceof WLDS\Module\Authors && 'maybe_disable_author_archive' === $fn[1] ) {
					$authors_priority = $priority;
				}
			}
		}
	}
	$canonical = has_action( 'template_redirect', 'redirect_canonical' );
	wlds_test_assert( false !== $authors_priority && false !== $canonical && $authors_priority < $canonical, 'authors: runs before redirect_canonical' );

	$args = apply_filters( 'wp_sitemaps_users_query_args', array() );
	wlds_test_assert( isset( $args['exclude'] ) && in_array( $member_id, $args['exclude'], false ), 'sitemap: agency member excluded' ); // phpcs:ignore WordPress.PHP.StrictInArray.FoundNonStrictFalse
	wlds_test_assert( isset( $args['exclude'] ) && ! in_array( $client_id, $args['exclude'], false ), 'sitemap: client author kept' ); // phpcs:ignore WordPress.PHP.StrictInArray.FoundNonStrictFalse

	$args = apply_filters( 'wp_sitemaps_users_query_args', array( 'exclude' => array( 999999 ) ) );
	wlds_test_assert( in_array( 999999, $args['exclude'], false ) && in_array( $member_id, $args['exclude'], false ), 'sitemap: existing exclude preserved' ); // phpcs:ignore WordPress.PHP.StrictInArray.FoundNonStrictFalse
} finally {
	wlds_test_cleanup();
}

wlds_test_finish();
