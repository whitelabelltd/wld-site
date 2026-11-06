<?php
/**
 * Agency Options
 *
 * Prevents non-agency users from changing the plugin's agency settings
 * (agency domains, support email, etc.) stored in wlds_options_extra.
 *
 * @package wld-site
 */

namespace WLDS\Module;

use WLDS\Helper\Agency;
use WLDS\Modules;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AgencyOptions Class
 */
class AgencyOptions extends Modules {

	/**
	 * Options that only agency members may change.
	 *
	 * @var string[]
	 */
	protected const LOCKED_OPTIONS = array( 'wlds_options_extra' );

	/**
	 * WP Hooks
	 *
	 * @return void
	 */
	public function hooks(): void {

		// Stop options.php from saving the locked options.
		add_filter( 'allowed_options', array( $this, 'filter_allowed_options' ) );

		// Block every other write path (REST API, other plugins).
		foreach ( self::LOCKED_OPTIONS as $option ) {
			add_filter( "pre_update_option_{$option}", array( $this, 'filter_pre_update_option' ), 10, 3 );
		}

		// Stop non-members from assigning an agency-domain email to a user.
		add_action( 'user_profile_update_errors', array( $this, 'filter_profile_errors' ), 10, 3 );
		add_filter( 'rest_pre_insert_user', array( $this, 'filter_rest_pre_insert_user' ), 10, 2 );
		add_filter( 'rest_request_before_callbacks', array( $this, 'filter_rest_request_before_callbacks' ), 10, 3 );

		// Stop non-members from editing, deleting or promoting existing agency users.
		add_filter( 'map_meta_cap', array( $this, 'block_agency_user_caps' ), 10, 4 );
	}

	/**
	 * Non-agency users may not edit, delete or promote existing agency-domain users
	 * (that would allow a password reset and a login as an agency member).
	 *
	 * @param string[] $caps    Required primitive capabilities.
	 * @param string   $cap     Capability being checked.
	 * @param int      $user_id Acting user ID.
	 * @param array    $args    Extra arguments; the first is the target user ID.
	 * @return array
	 */
	public function block_agency_user_caps( $caps, $cap, $user_id, $args ): array {
		$caps = (array) $caps;

		if ( ! in_array( $cap, array( 'edit_user', 'delete_user', 'remove_user', 'promote_user' ), true ) ) {
			return $caps;
		}

		$actor  = (int) $user_id;
		$target = isset( $args[0] ) ? (int) $args[0] : 0;

		if ( $actor <= 0 || $target <= 0 || $target === $actor ) {
			return $caps;
		}

		// Agency::is_member() only inspects the user's email, so this cannot recurse.
		if ( Agency::is_member( $target ) && ! Agency::is_member( $actor ) ) {
			return array( 'do_not_allow' );
		}

		return $caps;
	}

	/**
	 * Is the current user locked out of changing the agency options?
	 * Only applies to logged-in users, so cron, WP-CLI and migrations still work.
	 * If no agency domains are configured every user counts as a member.
	 *
	 * @return bool
	 */
	protected function is_locked(): bool {
		return function_exists( 'is_user_logged_in' ) && is_user_logged_in() && ! $this->is_agency_member();
	}

	/**
	 * Removes the locked options from every allowed options group.
	 *
	 * @param array $allowed_options Allowed options, keyed by option group.
	 * @return array
	 */
	public function filter_allowed_options( $allowed_options ): array {
		if ( ! is_array( $allowed_options ) ) {
			return (array) $allowed_options;
		}

		if ( ! $this->is_locked() ) {
			return $allowed_options;
		}

		foreach ( $allowed_options as $group => $options ) {
			if ( is_array( $options ) ) {
				$allowed_options[ $group ] = array_values( array_diff( $options, self::LOCKED_OPTIONS ) );
			}
		}

		return $allowed_options;
	}

	/**
	 * Keeps the old value when a locked user tries to change a locked option.
	 *
	 * @param mixed  $value     New option value.
	 * @param mixed  $old_value Old option value.
	 * @param string $option    Option name.
	 * @return mixed
	 */
	public function filter_pre_update_option( $value, $old_value, $option ) {
		if ( $this->is_locked() ) {
			return $old_value;
		}
		return $value;
	}

	/**
	 * Is this a new or changed email on an agency domain that the current user may not assign?
	 *
	 * @param int    $user_id User ID, 0 for a new user.
	 * @param string $email   Email address that is being set.
	 * @return bool
	 */
	protected function is_blocked_email( int $user_id, string $email ): bool {
		if ( '' === trim( $email, " \n\r\t\v\0" ) || ! $this->is_locked() ) {
			return false;
		}

		if ( $user_id > 0 ) {
			$current = get_userdata( $user_id );
			if ( $current && 0 === strcasecmp( trim( $email, " \n\r\t\v\0" ), trim( (string) $current->user_email, " \n\r\t\v\0" ) ) ) {
				return false; // Unchanged email.
			}
		}

		return Agency::is_agency_email( $email );
	}

	/**
	 * Adds an error when a non-member sets an agency-domain email in the profile / user screens.
	 *
	 * @param WP_Error $errors Errors object.
	 * @param bool     $update Whether this is a user update.
	 * @param object   $user   User object being saved.
	 * @return void
	 */
	public function filter_profile_errors( $errors, $update, $user ): void {
		if ( ! $errors instanceof WP_Error || ! is_object( $user ) || empty( $user->user_email ) ) {
			return;
		}

		$user_id = $update && ! empty( $user->ID ) ? (int) $user->ID : 0;
		if ( $this->is_blocked_email( $user_id, (string) $user->user_email ) ) {
			$errors->add( 'wlds_agency_email', _x( 'Only agency members can assign this email domain.', 'User edit error', 'wld-site' ) );
		}
	}

	/**
	 * Rejects REST user creates and updates where a non-member sets an agency-domain email.
	 * Core ignores a WP_Error returned from rest_pre_insert_user, so the real check runs in
	 * filter_rest_request_before_callbacks(). This is the safety net: it drops the email.
	 *
	 * @param object          $prepared_user User data prepared for insertion.
	 * @param WP_REST_Request $request       Request object.
	 * @return object
	 */
	public function filter_rest_pre_insert_user( $prepared_user, $request ) {
		if ( ! is_object( $prepared_user ) || empty( $prepared_user->user_email ) ) {
			return $prepared_user;
		}

		$user_id = ! empty( $prepared_user->ID ) ? (int) $prepared_user->ID : 0;
		if ( $this->is_blocked_email( $user_id, (string) $prepared_user->user_email ) ) {
			unset( $prepared_user->user_email );
		}

		return $prepared_user;
	}

	/**
	 * Rejects REST requests that create a user, or change a user's email, to an agency-domain
	 * email when the current user is not an agency member.
	 *
	 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response Response so far.
	 * @param array                                            $handler  Route handler.
	 * @param WP_REST_Request                                  $request  Request object.
	 * @return mixed
	 */
	public function filter_rest_request_before_callbacks( $response, $handler, $request ) {
		if ( is_wp_error( $response ) || ! $request instanceof \WP_REST_Request ) {
			return $response;
		}

		if ( ! in_array( $request->get_method(), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			return $response;
		}

		if ( ! preg_match( '#^/wp/v2/users(?:/(me|\d+))?/?$#i', $request->get_route(), $matches ) ) {
			return $response;
		}

		$email = $request->get_param( 'email' );
		if ( ! is_string( $email ) ) {
			return $response;
		}

		$user_id = 0;
		if ( isset( $matches[1] ) ) {
			$user_id = 'me' === strtolower( $matches[1] ) ? get_current_user_id() : (int) $matches[1];
		}

		if ( $this->is_blocked_email( $user_id, $email ) ) {
			return new WP_Error(
				'wlds_agency_email',
				_x( 'Only agency members can assign this email domain.', 'User edit error', 'wld-site' ),
				array( 'status' => 400 )
			);
		}

		return $response;
	}
}
new AgencyOptions();
