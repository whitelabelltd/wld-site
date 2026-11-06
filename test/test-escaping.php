<?php
/**
 * Tests for escaping of stored URLs and the Cloudflare beacon ID.
 *
 * Run from the Local site shell:
 * cd wp-content/plugins/wld-site && wp eval-file test/test-escaping.php
 *
 * @package wld-site
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

require __DIR__ . '/bootstrap.php';

// Beacon attribute.
$cf = new WLDS\Module\ThirdParty\Cloudflare();

$evil = "x' onload='alert(1)";
wlds_test_assert( '' === $cf->get_beacon_attribute( $evil ), 'beacon: quote cannot break attribute' );
wlds_test_assert( false === strpos( $cf->get_beacon_attribute( $evil ), 'onload=' ), 'beacon: no onload attribute' );

$good = str_repeat( 'a1b2c3d4', 4 );
wlds_test_assert(
	false !== strpos( $cf->get_beacon_attribute( $good ), "data-cf-beacon='{&quot;token&quot;:&quot;" . $good . "&quot;}'" ),
	'beacon: valid id rendered'
);
wlds_test_assert( '' === $cf->get_beacon_attribute( strtoupper( $good ) ), 'beacon: uppercase id rejected' );
wlds_test_assert( '' === $cf->get_beacon_attribute( 'abc123' ), 'beacon: short id rejected' );

// Injection into real script tags.
$real = wp_get_script_tag(
	array(
		'id'    => 'cf-beacon-js',
		'src'   => 'https://static.cloudflareinsights.com/beacon.min.js',
		'defer' => true,
	)
);
$out  = $cf->add_beacon_attribute( $real, $good );
wlds_test_assert(
	false !== strpos( $out, 'data-cf-beacon=' ) && false !== strpos( $out, 'id="cf-beacon-js"' ),
	'beacon: injected into real script tag'
);
$out = $cf->add_beacon_attribute( "<script id='cf-beacon-js' src='x'></script>", $good );
wlds_test_assert( false !== strpos( $out, 'data-cf-beacon=' ) && false !== strpos( $out, "id='cf-beacon-js'" ), 'beacon: injected into single-quoted tag' );
wlds_test_assert( $real === $cf->add_beacon_attribute( $real, $evil ), 'beacon: invalid id leaves tag unchanged' );

// Footer URL.
$force = static function ( $value ) {
	$value                   = is_array( $value ) ? $value : array();
	$value['managed_by_name'] = 'Evil';
	$value['managed_by_url']  = 'javascript:alert(1)';
	return $value;
};

$reset_cache = static function () {
	$cache = new ReflectionProperty( 'WLDS\Helper\Agency', 'extra_options' );
	if ( PHP_VERSION_ID < 80100 ) {
		$cache->setAccessible( true );
	}
	$cache->setValue( null, null );
};

try {
	$reset_cache();
	add_filter( 'option_wlds_options_extra', $force );
	$footer = apply_filters( 'admin_footer_text', '' );
	wlds_test_assert( false === strpos( $footer, 'javascript:' ), 'footer: javascript url stripped' );
	wlds_test_assert( false !== strpos( $footer, 'Evil' ), 'footer: name still rendered' );
} finally {
	remove_filter( 'option_wlds_options_extra', $force );
	$reset_cache();
}

wlds_test_finish();
