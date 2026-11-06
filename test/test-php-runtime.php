<?php
/**
 * Tests for PHP 8.4/8.5 runtime hygiene (no warnings from $_SERVER reads, string version, REST key).
 *
 * Run from the Local site shell:
 * cd wp-content/plugins/wld-site && wp eval-file test/test-php-runtime.php
 *
 * @package wld-site
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

require __DIR__ . '/bootstrap.php';

if ( ! class_exists( 'WLDS\Module\Hosting\Cloudways' ) ) {
	require_once WLDS_DIR . '/includes/modules/hosting/class-cloudways.php';
}
if ( ! class_exists( 'WLDS\Module\Hosting\Flywheel' ) ) {
	require_once WLDS_DIR . '/includes/modules/hosting/class-flywheel.php';
}

$invoke = static function ( $object, $method ) {
	$reflection = new ReflectionMethod( $object, $method );
	if ( PHP_VERSION_ID < 80100 ) {
		$reflection->setAccessible( true );
	}
	return $reflection->invoke( $object );
};

$issues    = array();
$keys      = array( 'REQUEST_URI', 'SERVER_SOFTWARE', 'DOCUMENT_ROOT' );
$saved     = array();
$dir       = rtrim( WLDS_DIR, '/\\' ) . DIRECTORY_SEPARATOR;
$prev      = set_error_handler(
	static function ( $errno, $errstr, $errfile ) use ( &$issues, $dir ) {
		if ( 0 === strpos( (string) $errfile, $dir ) ) {
			$issues[] = $errno . ': ' . $errstr . ' (' . $errfile . ')';
		}
		return true;
	},
	E_WARNING | E_DEPRECATED | E_NOTICE
);

try {
	foreach ( $keys as $key ) {
		if ( array_key_exists( $key, $_SERVER ) ) {
			$saved[ $key ] = $_SERVER[ $key ];
		}
		unset( $_SERVER[ $key ] );
	}

	( new \WLDS\Module\Security() )->xmlrpc_return_404();
	$invoke( new \WLDS\Module\Hosting\Flywheel(), 'is_flywheel_environment' );
	$invoke( new \WLDS\Module\Hosting\Cloudways(), 'is_environment' );

	wlds_test_assert( array() === $issues, 'cli: no warnings from $_SERVER reads' );
	if ( $issues ) {
		WP_CLI::log( implode( "\n", $issues ) );
	}

	// Force the local-environment branch, which is the one that used to return an int.
	$core = new class() extends \WLDS\Modules {
		/**
		 * No hooks needed.
		 *
		 * @return void
		 */
		public function hooks() {}

		/**
		 * Pretend to be a local environment.
		 *
		 * @return bool
		 */
		protected function is_local_env() : bool {
			return true;
		}
	};
	$version = $invoke( $core, 'get_resource_version' );
	wlds_test_assert( is_string( $version ), 'resource version is string' );

	$defaults = apply_filters( 'wlds_options_defaults', array() );
	wlds_test_assert( isset( $defaults['restrict_rest_api'] ) && 'users' === $defaults['restrict_rest_api'], 'rest default key' );
} finally {
	restore_error_handler();
	foreach ( $keys as $key ) {
		if ( array_key_exists( $key, $saved ) ) {
			$_SERVER[ $key ] = $saved[ $key ];
		} else {
			unset( $_SERVER[ $key ] );
		}
	}
	wlds_test_cleanup();
}

wlds_test_finish();
