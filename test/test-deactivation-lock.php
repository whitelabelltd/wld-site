<?php
/**
 * Tests for server-side enforcement of "disable plugin deactivation".
 *
 * Run from the Local site shell:
 * cd wp-content/plugins/wld-site && wp eval-file test/test-deactivation-lock.php
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
$basename  = plugin_basename( WLDS_FILE );

$force_option = static function ( $enabled ) {
	return static function ( $value ) use ( $enabled ) {
		$value                                = is_array( $value ) ? $value : array();
		$value['disable_plugin_deactivation'] = $enabled ? 'yes' : false;
		$value['agency_domains']              = array( 'agency.test' );
		return $value;
	};
};

// Agency::get_options_extra() caches the option in a static property, so reset it.
$reset_cache = static function () {
	$cache = new ReflectionProperty( 'WLDS\Helper\Agency', 'extra_options' );
	if ( PHP_VERSION_ID < 80100 ) {
		$cache->setAccessible( true ); // Needed on PHP 8.0.
	}
	$cache->setValue( null, null );
};

$on  = $force_option( true );
$off = $force_option( false );

try {
	$reset_cache();
	add_filter( 'option_wlds_options_extra', $on );

	wlds_test_assert( false === user_can( $client_id, 'deactivate_plugin', $basename ), 'non-member: cannot deactivate' );
	wlds_test_assert( true === user_can( $client_id, 'deactivate_plugin', 'hello.php' ), 'non-member: other plugins unaffected' );
	wlds_test_assert( true === user_can( $member_id, 'deactivate_plugin', $basename ), 'member: can deactivate' );

	remove_filter( 'option_wlds_options_extra', $on );
	$reset_cache();
	add_filter( 'option_wlds_options_extra', $off );
	wlds_test_assert( true === user_can( $client_id, 'deactivate_plugin', $basename ), 'option off: non-member can deactivate' );
	remove_filter( 'option_wlds_options_extra', $off );
} finally {
	$reset_cache();
	remove_filter( 'option_wlds_options_extra', $on );
	remove_filter( 'option_wlds_options_extra', $off );
}

wlds_test_finish();
