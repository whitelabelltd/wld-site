<?php
/**
 * Shared bootstrap for the WP-CLI test scripts.
 *
 * Usage, from a test script run with `wp eval-file test/<file>.php`:
 *
 *   require __DIR__ . '/bootstrap.php';
 *   $users = wlds_test_users(); // array( 'member' => int, 'client' => int ).
 *   try { wlds_test_assert( true, 'label' ); } finally { wlds_test_cleanup(); }
 *   wlds_test_finish();
 *
 * Provides: wlds_test_assert(), wlds_test_users(), wlds_test_cleanup(), wlds_test_finish().
 * It forces the agency domain list to agency.test and creates two administrators:
 * wlds-member (member@agency.test) and wlds-client (client@client.test).
 * wlds_test_cleanup() is idempotent and also registered as a shutdown function,
 * so the users are removed even when a test throws or WP_CLI::error() exits.
 * Calling it in a `finally` block is optional but keeps the cleanup explicit.
 *
 * @package wld-site
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/user.php';

$GLOBALS['wlds_test_failures'] = 0;
$GLOBALS['wlds_test_users']    = array();

/**
 * Logs a test result.
 *
 * @param bool   $condition Condition that must be true.
 * @param string $label     Test label.
 * @return void
 */
function wlds_test_assert( bool $condition, string $label ): void {
	if ( $condition ) {
		WP_CLI::log( 'PASS: ' . $label );
	} else {
		WP_CLI::log( 'FAIL: ' . $label );
		++$GLOBALS['wlds_test_failures'];
	}
}

/**
 * Agency domains used by the tests.
 * Named so a test can remove_filter() it to simulate "no domains configured".
 *
 * @return string[]
 */
function wlds_test_agency_domains(): array {
	return array( 'agency.test' );
}

/**
 * Returns the test user IDs.
 *
 * @return array{member:int,client:int}
 */
function wlds_test_users(): array {
	return $GLOBALS['wlds_test_users'];
}

/**
 * Deletes the test users. Safe to call more than once.
 *
 * @return void
 */
function wlds_test_cleanup(): void {
	wp_set_current_user( 0 );
	foreach ( array( 'wlds-member', 'wlds-client' ) as $login ) {
		$user = get_user_by( 'login', $login );
		if ( $user ) {
			wp_delete_user( $user->ID );
		}
	}
	$GLOBALS['wlds_test_users'] = array();
}

/**
 * Reports the final result and exits.
 *
 * @return void
 */
function wlds_test_finish(): void {
	if ( $GLOBALS['wlds_test_failures'] > 0 ) {
		WP_CLI::error( $GLOBALS['wlds_test_failures'] . ' test(s) failed.' );
	}
	WP_CLI::success( 'All tests passed.' );
}

add_filter( 'wlds_agency_domains', 'wlds_test_agency_domains' );

// Remove leftovers from an aborted run, then create the users.
wlds_test_cleanup();
register_shutdown_function( 'wlds_test_cleanup' );

$wlds_test_member_id = wp_insert_user(
	array(
		'user_login' => 'wlds-member',
		'user_email' => 'member@agency.test',
		'user_pass'  => wp_generate_password(),
		'role'       => 'administrator',
	)
);
$wlds_test_client_id = wp_insert_user(
	array(
		'user_login' => 'wlds-client',
		'user_email' => 'client@client.test',
		'user_pass'  => wp_generate_password(),
		'role'       => 'administrator',
	)
);
if ( is_wp_error( $wlds_test_member_id ) || is_wp_error( $wlds_test_client_id ) ) {
	WP_CLI::error( 'Could not create test users.' );
}
$GLOBALS['wlds_test_users'] = array(
	'member' => (int) $wlds_test_member_id,
	'client' => (int) $wlds_test_client_id,
);
