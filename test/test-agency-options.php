<?php
/**
 * Tests for the AgencyOptions module.
 *
 * Run from the Local site shell:
 * cd wp-content/plugins/wld-site && wp eval-file test/test-agency-options.php
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

$had_original = false !== get_option( 'wlds_options_extra', false );
$original     = get_option( 'wlds_options_extra' );
$seed         = array( 'agency_domains' => array( 'agency.test' ) );

try {
	wlds_test_assert( class_exists( 'WLDS\Module\AgencyOptions' ), 'AgencyOptions module loaded' );

	wp_set_current_user( 0 );
	update_option( 'wlds_options_extra', $seed, false );

	// Non-member.
	wp_set_current_user( $client_id );
	update_option( 'wlds_options_extra', array( 'agency_domains' => array( 'evil.test' ) ) );
	$stored = get_option( 'wlds_options_extra' );
	wlds_test_assert( is_array( $stored ) && array( 'agency.test' ) === ( $stored['agency_domains'] ?? null ), 'non-member: wlds_options_extra update blocked' );

	$out = apply_filters( 'allowed_options', array( 'options' => array( 'wlds_options_extra', 'blogname' ) ) );
	wlds_test_assert( ! in_array( 'wlds_options_extra', $out['options'], true ) && in_array( 'blogname', $out['options'], true ), 'non-member: stripped from allowed_options[options]' );
	wlds_test_assert( array_values( $out['options'] ) === $out['options'], 'non-member: options list re-indexed' );

	// Member.
	wp_set_current_user( $member_id );
	$new = array( 'agency_domains' => array( 'agency.test', 'second.test' ) );
	wlds_test_assert( $new === apply_filters( 'pre_update_option_wlds_options_extra', $new, $seed, 'wlds_options_extra' ), 'member: update allowed' );
	$out = apply_filters( 'allowed_options', array( 'options' => array( 'wlds_options_extra', 'blogname' ) ) );
	wlds_test_assert( in_array( 'wlds_options_extra', $out['options'], true ), 'member: allowed_options keeps wlds_options_extra' );

	// No logged-in user (cron, WP-CLI).
	wp_set_current_user( 0 );
	wlds_test_assert( $new === apply_filters( 'pre_update_option_wlds_options_extra', $new, $seed, 'wlds_options_extra' ), 'no user: update allowed' );

	// Agency-domain emails (S1).
	$rest_user = function ( string $method, string $route, array $params ) {
		$request = new WP_REST_Request( $method, $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return rest_do_request( $request );
	};

	$delete_x = function () {
		$created = get_user_by( 'login', 'wlds-x' );
		if ( $created ) {
			wp_delete_user( $created->ID );
		}
	};

	wp_set_current_user( $client_id );
	$res = $rest_user(
		'POST',
		'/wp/v2/users',
		array(
			'username' => 'wlds-x',
			'email'    => 'x@agency.test',
			'password' => wp_generate_password( 20 ),
		)
	);
	wlds_test_assert( 400 === $res->get_status() && false === get_user_by( 'login', 'wlds-x' ), 'non-member: REST create with agency email rejected' );
	$delete_x(); // Only needed when the check above failed.

	$res = $rest_user( 'POST', '/wp/v2/users/' . $client_id, array( 'email' => 'x@agency.test' ) );
	wlds_test_assert( 400 === $res->get_status(), 'non-member: REST email change to agency domain rejected' );

	$res = $rest_user( 'POST', '/wp/v2/users/me', array( 'email' => 'x@agency.test' ) );
	$after = get_userdata( $client_id );
	wlds_test_assert( 400 === $res->get_status() && $after && 'client@client.test' === $after->user_email, 'non-member: REST /users/me agency email rejected' );

	try {
		$res = $rest_user(
			'POST',
			'/wp/v2/Users',
			array(
				'username' => 'wlds-x',
				'email'    => 'x@agency.test',
				'password' => wp_generate_password( 20 ),
			)
		);
		wlds_test_assert( 400 === $res->get_status() && false === get_user_by( 'login', 'wlds-x' ), 'non-member: REST case-variant route rejected' );
	} finally {
		$delete_x();
	}

	$e = new WP_Error();
	do_action_ref_array(
		'user_profile_update_errors',
		array( &$e, true, (object) array( 'ID' => $client_id, 'user_email' => 'x@agency.test' ) )
	);
	wlds_test_assert( $e->has_errors(), 'non-member: profile errors added' );

	$e = new WP_Error();
	do_action_ref_array(
		'user_profile_update_errors',
		array( &$e, true, (object) array( 'ID' => $member_id, 'user_email' => 'member@agency.test' ) )
	);
	wlds_test_assert( ! $e->has_errors(), 'non-member: unchanged agency email allowed' );

	wp_set_current_user( $member_id );
	try {
		$res = $rest_user(
			'POST',
			'/wp/v2/users',
			array(
				'username' => 'wlds-x',
				'email'    => 'x@agency.test',
				'password' => wp_generate_password( 20 ),
			)
		);
		wlds_test_assert( 201 === $res->get_status(), 'member: REST create with agency email allowed' );
	} finally {
		$delete_x();
	}

	// Agency users are protected from non-member admins.
	wp_set_current_user( $client_id );
	wlds_test_assert( ! user_can( $client_id, 'edit_user', $member_id ), 'non-member: cannot edit agency user' );
	wlds_test_assert( ! user_can( $client_id, 'delete_user', $member_id ), 'non-member: cannot delete agency user' );
	wlds_test_assert( user_can( $client_id, 'edit_user', $client_id ), 'non-member: can edit self' );
	$other_id = wp_insert_user(
		array(
			'user_login' => 'wlds_other_test',
			'user_pass'  => wp_generate_password(),
			'user_email' => 'other@client.test',
			'role'       => 'editor',
		)
	);
	try {
		wlds_test_assert( ! is_wp_error( $other_id ) && user_can( $client_id, 'edit_user', $other_id ), 'non-member: can edit other non-member' );
		wlds_test_assert( user_can( $member_id, 'edit_user', $other_id ), 'member: can edit non-member' );
	} finally {
		if ( ! is_wp_error( $other_id ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $other_id );
		}
	}
	$member2_id = wp_insert_user(
		array(
			'user_login' => 'wlds_member2_test',
			'user_pass'  => wp_generate_password(),
			'user_email' => 'member2@agency.test',
			'role'       => 'editor',
		)
	);
	try {
		wlds_test_assert( ! is_wp_error( $member2_id ) && user_can( $member_id, 'edit_user', $member2_id ), 'member: can edit agency user' );
	} finally {
		if ( ! is_wp_error( $member2_id ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $member2_id );
		}
	}
	$req = new WP_REST_Request( 'POST', '/wp/v2/users/' . $member_id );
	$req->set_param( 'password', 'Sup3r-Secret-Pass-9!x' );
	$res = rest_do_request( $req );
	wlds_test_assert( $res->get_status() >= 400, 'non-member: REST password change on agency user rejected' );

	// First activation: no domains configured, so everyone counts as a member.
	remove_filter( 'wlds_agency_domains', 'wlds_test_agency_domains' );
	delete_option( 'wlds_options_extra' );
	// Agency::get_options_extra() caches the option in a static property, so reset it.
	$cache = new ReflectionProperty( 'WLDS\Helper\Agency', 'extra_options' );
	if ( PHP_VERSION_ID < 80100 ) {
		$cache->setAccessible( true ); // Needed on PHP 8.0.
	}
	$cache->setValue( null, null );
	wp_set_current_user( $client_id );
	\WLDS\Helper\Tools::set_json_options( array( 'agency_domains' => array( 'agency.test' ) ) );
	$stored = get_option( 'wlds_options_extra' );
	wlds_test_assert( is_array( $stored ) && array( 'agency.test' ) === ( $stored['agency_domains'] ?? null ), 'first activation: options saved when no domains configured' );
	add_filter( 'wlds_agency_domains', 'wlds_test_agency_domains' );
} finally {
	wp_set_current_user( 0 );
	if ( ! has_filter( 'wlds_agency_domains', 'wlds_test_agency_domains' ) ) {
		add_filter( 'wlds_agency_domains', 'wlds_test_agency_domains' );
	}
	if ( $had_original ) {
		update_option( 'wlds_options_extra', $original, false );
	} else {
		delete_option( 'wlds_options_extra' );
	}
	wlds_test_cleanup();
}

wlds_test_finish();
