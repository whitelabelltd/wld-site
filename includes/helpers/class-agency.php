<?php
/**
 * Agency
 *
 * @package wld-site
 */

namespace WLDS\Helper;

use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Agency Class
 */
class Agency {

	/**
	 * Holds Extra Options
	 *
	 * @var array
	 */
	private static $extra_options;

	/**
	 * Gets a list of Agency Domains
	 *
	 * @return array|false
	 */
	public static function get_agency_domains() {
		// Return the list with a filter.
		return apply_filters( 'wlds_agency_domains', self::get_option_extra( 'agency_domains' ) );
	}

	/**
	 * Checks if the current user is a member of the agency
	 * If no agency domains are set in options, then always return TRUE
	 *
	 * @param int $user_id WP User ID.
	 * @return bool
	 */
	public static function is_member( $user_id = 0 ) : bool {
		if ( function_exists( 'wp_get_current_user' ) ) {

			// Get current user email.
			if ( 0 === $user_id ) {
				$user = wp_get_current_user();
			} else {
				$user = new WP_User( $user_id );
				if ( is_wp_error( $user ) ) {
					return false;
				}
			}

			if ( 0 !== $user->ID ) {
				// Get Email Domain.
				$email_domain = self::get_email_domain( $user->user_email );
				if ( $email_domain ) {
					// Is the email from a whitelisted email domain list?
					$domains = self::get_agency_domains();

					// If no agency domains exist, return true.
					if ( ! $domains ) {
						return true;
					}

					// Does the Email Domain match an existing domain name?
					if ( in_array( $email_domain, $domains, true ) ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Gets the Email Domain Name in lowercase
	 *
	 * @param string $email Email Address.
	 * @return false|string
	 */
	protected static function get_email_domain( $email = '' ) {
		if ( is_email( $email ) ) {
			return strtolower( substr( $email, strrpos( $email, '@' ) + 1 ) );
		}
		return false;
	}

	/**
	 * Checks if the current user is an admin or a member of the agency
	 *
	 * @return bool
	 */
	public static function is_member_or_admin() : bool {
		if ( current_user_can( 'activate_plugins' ) ||
			( self::is_member() && current_user_can( 'delete_pages' ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Checks if the current user is a member of the agency
	 * Also checks if the user is an editor or above
	 *
	 * @return bool
	 */
	public static function is_member_editor_plus() : bool {
		if ( current_user_can( 'delete_pages' ) &&
			self::is_member()
		) {
			return true;
		}
		return false;
	}

	/**
	 * Is the extra option enabled
	 *
	 * @param string $option_name Option Name.
	 * @return bool
	 */
	public static function is_option_extra_enabled( $option_name ) {
		$value = self::get_option_extra( $option_name );
		if ( 'yes' === $value || true === $value ) {
			return true;
		}
		return false;
	}

	/**
	 * Gets the extra option by name
	 *
	 * @param string $option_name Option Name.
	 * @return false|mixed
	 */
	public static function get_option_extra( $option_name ) {
		$data = self::get_options_extra();
		if ( $data && array_key_exists( $option_name, $data ) ) {
			return $data[ $option_name ];
		}
		return false;
	}

	/**
	 * Get Extra Options
	 *
	 * @return false|array
	 */
	public static function get_options_extra() {
		if ( ! self::$extra_options ) {
			$option_name = 'wlds_options_extra';
			$data        = get_option( $option_name );
			if ( $data ) {
				// Set Options.
				self::$extra_options = $data;

				// Return Data.
				return self::$extra_options;
			}
			return false;
		} else {
			return self::$extra_options;
		}
	}
}
