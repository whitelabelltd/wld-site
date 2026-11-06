<?php
/**
 * Tests for IP range checks and Cloudflare real-IP trust.
 *
 * Run from the Local site shell:
 * cd wp-content/plugins/wld-site && wp eval-file test/test-iputil.php
 *
 * @package wld-site
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

require __DIR__ . '/bootstrap.php';

use WLDS\Helper\IP;

wlds_test_assert( true === IP::ipv6_in_range( '2400:cb00::1', '2400:cb00::/32' ), 'ipv6: in /32 range' );
wlds_test_assert( false === IP::ipv6_in_range( '2400:cb01::1', '2400:cb00::/32' ), 'ipv6: outside /32 range' );
wlds_test_assert( true === IP::ipv6_in_range( '2a06:98c7::1', '2a06:98c0::/29' ), 'ipv6: in /29 range' );
wlds_test_assert( false === IP::ipv6_in_range( '2a06:98c8::1', '2a06:98c0::/29' ), 'ipv6: outside /29 range' );
wlds_test_assert( true === IP::ipv4_in_range( '173.245.48.1', '173.245.48.0/20' ), 'ipv4: in range' );
wlds_test_assert( false === IP::ipv4_in_range( '198.51.100.1', '173.245.48.0/20' ), 'ipv4: outside range' );

$cloudflare = new WLDS\Module\ThirdParty\Cloudflare();
$method     = new ReflectionMethod( $cloudflare, 'set_real_ip' );
if ( PHP_VERSION_ID < 80100 ) {
	$method->setAccessible( true );
}

$option_name  = 'wlds_cloudflare_ips';
$had_option   = false !== get_option( $option_name );
$orig_option  = get_option( $option_name );
$orig_remote  = $_SERVER['REMOTE_ADDR'] ?? null;
$orig_cf      = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null;
$warnings     = 0;
$run          = static function ( $remote, $cf ) use ( $method, $cloudflare ) {
	$_SERVER['REMOTE_ADDR'] = $remote;
	if ( null === $cf ) {
		unset( $_SERVER['HTTP_CF_CONNECTING_IP'] );
	} else {
		$_SERVER['HTTP_CF_CONNECTING_IP'] = $cf;
	}
	$method->invoke( $cloudflare );
	return $_SERVER['REMOTE_ADDR'];
};

set_error_handler(
	static function () use ( &$warnings ) {
		++$warnings;
		return true;
	}
);

try {
	update_option(
		$option_name,
		array(
			'ipv4' => array( '173.245.48.0/20' ),
			'ipv6' => array( '2400:cb00::/32' ),
		)
	);

	wlds_test_assert( '173.245.48.1' === $run( '173.245.48.1', 'not-an-ip' ), 'invalid CF-Connecting-IP ignored (ipv4)' );
	wlds_test_assert( '2400:cb00::1' === $run( '2400:cb00::1', 'not-an-ip' ), 'invalid CF-Connecting-IP ignored (ipv6)' );
	wlds_test_assert( '203.0.113.5' === $run( '173.245.48.1', '203.0.113.5' ), 'valid CF-Connecting-IP applied from CF ipv4' );
	wlds_test_assert( '203.0.113.5' === $run( '2400:cb00::1', '203.0.113.5' ), 'valid CF-Connecting-IP applied from CF ipv6' );
	wlds_test_assert( '198.51.100.1' === $run( '198.51.100.1', '203.0.113.5' ), 'non-CF ipv4 REMOTE_ADDR left untouched' );
	wlds_test_assert( '2400:cb01::1' === $run( '2400:cb01::1', '203.0.113.5' ), 'non-CF ipv6 REMOTE_ADDR left untouched' );

	$warnings = 0;
	$run( '173.245.48.1', null );
	wlds_test_assert( 0 === $warnings, 'CF-Connecting-IP unset: no warnings' );

	update_option( $option_name, array( 'ipv4' => array( '173.245.48.0/20' ) ) );
	$warnings = 0;
	$run( '2400:cb00::1', '203.0.113.5' );
	wlds_test_assert( 0 === $warnings, 'missing ipv6 ranges: no warnings' );

	update_option( $option_name, array( 'ipv6' => array( '2400:cb00::/32' ) ) );
	$warnings = 0;
	$run( '173.245.48.1', '203.0.113.5' );
	wlds_test_assert( 0 === $warnings, 'missing ipv4 ranges: no warnings' );
} finally {
	restore_error_handler();
	if ( $had_option ) {
		update_option( $option_name, $orig_option );
	} else {
		delete_option( $option_name );
	}
	if ( null === $orig_remote ) {
		unset( $_SERVER['REMOTE_ADDR'] );
	} else {
		$_SERVER['REMOTE_ADDR'] = $orig_remote;
	}
	if ( null === $orig_cf ) {
		unset( $_SERVER['HTTP_CF_CONNECTING_IP'] );
	} else {
		$_SERVER['HTTP_CF_CONNECTING_IP'] = $orig_cf;
	}
}

wlds_test_finish();
