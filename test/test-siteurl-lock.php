<?php
/**
 * Tests for the SiteUrl module.
 *
 * Run from the Local site shell:
 * cd wp-content/plugins/wld-site && wp eval-file test/test-siteurl-lock.php
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

$original = get_option( 'siteurl' );

try {
	wlds_test_assert( class_exists( 'WLDS\Module\SiteUrl' ), 'SiteUrl module loaded' );

	$input = array(
		'general' => array( 'blogname', 'siteurl', 'home' ),
		'reading' => array( 'siteurl' ),
	);

	// Non-member.
	wp_set_current_user( $client_id );
	$out = apply_filters( 'allowed_options', $input );
	wlds_test_assert( in_array( 'blogname', $out['general'], true ) && ! in_array( 'siteurl', $out['general'], true ) && ! in_array( 'home', $out['general'], true ), 'non-member: siteurl/home removed from general' );
	wlds_test_assert( array_values( $out['general'] ) === $out['general'], 'non-member: general list re-indexed' );
	wlds_test_assert( array( 'siteurl' ) === $out['reading'], 'non-member: other groups untouched' );
	wlds_test_assert( array( 'reading' => array( 'x' ) ) === apply_filters( 'allowed_options', array( 'reading' => array( 'x' ) ) ), 'non-member: missing general key returned unchanged' );

	update_option( 'siteurl', 'https://changed.test' );
	wlds_test_assert( get_option( 'siteurl' ) === $original, 'non-member: update_option(siteurl) blocked' );
	wlds_test_assert( 'https://old.test' === apply_filters( 'pre_update_option_home', 'https://changed.test', 'https://old.test', 'home' ), 'non-member: home update blocked' );

	$request = new WP_REST_Request( 'POST', '/wp/v2/settings' );
	$request->set_param( 'url', 'https://changed.test' );
	rest_do_request( $request );
	wlds_test_assert( get_option( 'siteurl' ) === $original, 'non-member: REST settings url blocked' );

	// Member.
	wp_set_current_user( $member_id );
	// Core's option_update_filter may merge registered settings in, so check membership, not equality.
	$out = apply_filters( 'allowed_options', $input );
	wlds_test_assert( in_array( 'siteurl', $out['general'], true ) && in_array( 'home', $out['general'], true ), 'member: allowed_options keeps siteurl/home' );
	wlds_test_assert( 'https://changed.test' === apply_filters( 'pre_update_option_siteurl', 'https://changed.test', $original, 'siteurl' ), 'member: siteurl update allowed' );

	// No logged-in user (cron, WP-CLI, migrations).
	wp_set_current_user( 0 );
	wlds_test_assert( 'https://changed.test' === apply_filters( 'pre_update_option_siteurl', 'https://changed.test', $original, 'siteurl' ), 'no user: siteurl update allowed' );

	// Settings > General script.
	$module = new \WLDS\Module\SiteUrl();
	wlds_test_assert( method_exists( $module, 'enqueue_scripts' ), 'enqueue_scripts exists' );
	if ( ! method_exists( $module, 'enqueue_scripts' ) ) {
		throw new Exception( 'enqueue_scripts missing' ); // Jumps to finally, so cleanup still runs.
	}

	wp_set_current_user( $member_id );
	$module->enqueue_scripts( 'options-general.php' );
	wlds_test_assert( ! wp_script_is( 'wlds_siteurl_lock', 'enqueued' ), 'member: lock script not enqueued' );

	wp_set_current_user( $client_id );
	$module->enqueue_scripts( 'options-reading.php' );
	wlds_test_assert( ! wp_script_is( 'wlds_siteurl_lock', 'enqueued' ), 'non-member: lock script not enqueued on other pages' );

	$module->enqueue_scripts( 'options-general.php' );
	wlds_test_assert( wp_script_is( 'wlds_siteurl_lock', 'enqueued' ), 'non-member: lock script enqueued on options-general.php' );
	wlds_test_assert( str_contains( (string) wp_scripts()->get_data( 'wlds_siteurl_lock', 'data' ), 'wlds_s' ), 'non-member: wlds_s localized' );
} finally {
	wp_set_current_user( 0 );
	if ( get_option( 'siteurl' ) !== $original ) {
		update_option( 'siteurl', $original );
	}
	wlds_test_cleanup();
}

wlds_test_finish();
