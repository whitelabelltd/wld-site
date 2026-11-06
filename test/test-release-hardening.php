<?php
/**
 * Tests for the release hardening: response headers, client.json import and agency domain cleanup.
 *
 * Run from the Local site shell:
 * cd wp-content/plugins/wld-site && wp eval-file test/test-release-hardening.php
 *
 * @package wld-site
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

require __DIR__ . '/bootstrap.php';

$had_original = false !== get_option( 'wlds_options_extra', false );
$original     = get_option( 'wlds_options_extra' );
$tmp_dir      = trailingslashit( get_temp_dir() ) . 'wlds-test-' . wp_generate_password( 8, false );
$xfo_filter   = static function () {
	return 'ALLOWALL';
};

// Agency::get_options_extra() caches the option in a static property, so reset it.
$reset_cache = static function () {
	$cache = new ReflectionProperty( 'WLDS\Helper\Agency', 'extra_options' );
	if ( PHP_VERSION_ID < 80100 ) {
		$cache->setAccessible( true ); // Needed on PHP 8.0.
	}
	$cache->setValue( null, null );
};

try {
	// Headers.
	$headers = apply_filters( 'wp_headers', array() );
	wlds_test_assert( 'nosniff' === ( $headers['X-Content-Type-Options'] ?? null ), 'headers: nosniff set' );
	wlds_test_assert( 'strict-origin-when-cross-origin' === ( $headers['Referrer-Policy'] ?? null ), 'headers: referrer-policy set' );

	$headers = apply_filters(
		'wp_headers',
		array(
			'X-Content-Type-Options' => 'custom',
			'Referrer-Policy'        => 'no-referrer',
		)
	);
	wlds_test_assert( 'custom' === $headers['X-Content-Type-Options'] && 'no-referrer' === $headers['Referrer-Policy'], 'headers: existing header not overwritten' );

	add_filter( 'wlds_x_frame_options', $xfo_filter );
	$headers = apply_filters( 'wp_headers', array() );
	remove_filter( 'wlds_x_frame_options', $xfo_filter );
	wlds_test_assert( 'SAMEORIGIN' === ( $headers['X-Frame-Options'] ?? null ), 'headers: invalid XFO falls back to SAMEORIGIN' );

	// Agency domains.
	$reset_cache();
	WLDS\Helper\Tools::set_json_options( array( 'agency_domains' => array( '', 5, 'Agency.Test' ) ) );
	$stored = get_option( 'wlds_options_extra' );
	wlds_test_assert( is_array( $stored ) && array( 'agency.test' ) === ( $stored['agency_domains'] ?? null ), 'json options: empty and non-string domains dropped' );

	// Client file import.
	wp_mkdir_p( $tmp_dir );
	$data = array( 'support_email_to' => 'a@b.test' );

	file_put_contents( $tmp_dir . '/client.json', wp_json_encode( $data ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$read = function_exists( 'WLDS\_wlds_read_client_file' ) ? WLDS\_wlds_read_client_file( $tmp_dir ) : null;
	wlds_test_assert( $data === $read && ! file_exists( $tmp_dir . '/client.json' ), 'client file: client.json read and deleted' );

	wlds_test_assert( function_exists( 'WLDS\_wlds_read_client_file' ) && null === WLDS\_wlds_read_client_file( $tmp_dir ), 'client file: none present returns null' );

	file_put_contents( $tmp_dir . '/client.json', '{not json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$read = function_exists( 'WLDS\_wlds_read_client_file' ) ? WLDS\_wlds_read_client_file( $tmp_dir ) : false;
	wlds_test_assert( null === $read && file_exists( $tmp_dir . '/client.json' ), 'client file: invalid json kept on disk' );
	unlink( $tmp_dir . '/client.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	file_put_contents( $tmp_dir . '/client.json.php', "<?php exit; ?>\n" . wp_json_encode( $data ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$read = function_exists( 'WLDS\_wlds_read_client_file' ) ? WLDS\_wlds_read_client_file( $tmp_dir ) : false;
	wlds_test_assert( null === $read && file_exists( $tmp_dir . '/client.json.php' ), 'client file: client.json.php is not read' );
} finally {
	remove_filter( 'wlds_x_frame_options', $xfo_filter );
	foreach ( array( '/client.json.php', '/client.json' ) as $leftover ) {
		if ( file_exists( $tmp_dir . $leftover ) ) {
			unlink( $tmp_dir . $leftover ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
	if ( is_dir( $tmp_dir ) ) {
		rmdir( $tmp_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	if ( $had_original ) {
		update_option( 'wlds_options_extra', $original, false );
	} else {
		delete_option( 'wlds_options_extra' );
	}
	$reset_cache();
}

wlds_test_finish();
