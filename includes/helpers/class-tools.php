<?php
/**
 * Tools
 *
 * @package wld-site
 */

namespace WLDS\Helper;

/**
 * Tools Class
 */
class Tools {

	/**
	 * Holds the options
	 *
	 * @var string
	 */
	public static $option_name = 'wlds_options_user';

	/**
	 * Sanitise all settings
	 *
	 * @param  array  $settings New settings.
	 * @param  string $type defaults to text.
	 * @return array
	 */
	public static function sanitise_settings( $settings, $type = 'text' ) {
		foreach ( $settings as $key => $setting ) {
			switch ( $type ) {
				default:
				case 'text':
					$settings[ $key ] = sanitize_text_field( $setting );
					break;
				case 'bool':
					if ( 'yes' === $setting || 'no' === $setting ) {
						$settings[ $key ] = $setting;
					}
					break;
			}
		}
		return $settings;
	}

	/**
	 * Running in a Local Environment?
	 *
	 * @return bool
	 */
	public static function is_local_env() {
		if ( function_exists( 'wp_get_environment_type' ) ) {
			if ( 'local' === wp_get_environment_type() ) {
				return true;
			}
		} else {
			if ( isset( $_SERVER['SERVER_NAME'] ) ) {
				// phpcs:disable
				if ( str_contains( $_SERVER['SERVER_NAME'], '.local' ) ) {
					// phpcs:enable
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Sets the site admin email
	 *
	 * @param string $email Email Address to be set for site admin.
	 * @return bool
	 */
	public static function set_site_admin_email( $email = '' ) : bool {
		if ( $email &&
			is_email( $email )
		) {

			// Prevent change email notification from being sent.
			add_filter( 'send_site_admin_email_change_email', '__return_false', 99 );
			remove_action( 'update_option_new_admin_email', 'update_option_new_admin_email', 10, 2 );
			remove_action( 'update_option_admin_email', 'wp_site_admin_email_change_notification', 10, 3 );

			// Sets the new Admin Email.
			update_option( 'admin_email', $email );
			update_option( 'new_admin_email', $email );

			// Done.
			return true;
		}
		return false;
	}

	/**
	 * Set WordPress default settings
	 *
	 * @return void
	 */
	public static function set_wp_defaults() {

		// Disable User Registrations.
		update_option( 'users_can_register', 0 );

		// Set Default Ping-backs.
		update_option( 'default_pingback_flag', 0 );
		update_option( 'default_ping_status', 'closed' );

		// Close Comments and Disable Comments.
		update_option( 'default_comment_status', 'closed' );
		update_option( 'require_name_email', 0 );
		update_option( 'comment_registration', 1 );
		update_option( 'show_comments_cookies_opt_in', 0 );
		update_option( 'thread_comments', 0 );
		update_option( 'comments_notify', 0 );
		update_option( 'moderation_notify', 0 );
		update_option( 'comment_moderation', 1 );
		update_option( 'comment_previously_approved', 0 );

		// Disable Avatars.
		update_option( 'show_avatars', 0 );
	}

	/**
	 * Updates the site language
	 *
	 * @param string $locale WP Locale String.
	 * @param string $timezone_string PHP Timezone String.
	 *
	 * @return void
	 */
	public static function set_site_language( $locale = '', $timezone_string = '' ) : void {
		if ( $locale && $timezone_string ) {

			// Get current Locale.
			$user_language_old = get_user_locale();

			// Load Translation Install.
			require_once ABSPATH . 'wp-admin/includes/translation-install.php';
			$language = wp_download_language_pack( $locale );
			if ( $language ) {
				// Set WP Locale.
				switch_to_locale( $locale );

				// Update Option, can only be done once the language pack has been installed.
				update_option( 'WPLANG', $locale );

				// Update Timezone.
				update_option( 'timezone_string', $timezone_string );

				/*
				 * Switch user translation in case WPLANG was changed.
				 * The global $locale is used in get_locale() which is
				 * used as a fallback in get_user_locale().
				 */
				$user_language_new = get_user_locale();
				if ( $user_language_old !== $user_language_new ) {
					load_default_textdomain( $user_language_new );
				}
			}
		}
	}

	/**
	 * Sets the options from the json file
	 *
	 * @param array $file_data data from JSON file in array form.
	 *
	 * @return void
	 * @noinspection HttpUrlsUsage*/
	public static function set_json_options( $file_data = array() ) {
		if ( $file_data ) {
			$settings = array();

			// Are we disabling plugin activation?
			if ( isset( $file_data['disable_plugin_deactivation'] ) &&
				$file_data['disable_plugin_deactivation']
			) {
				$settings['disable_plugin_deactivation'] = true;
			} else {
				$settings['disable_plugin_deactivation'] = false;
			}

			// Get Agency Domains.
			if ( isset( $file_data['agency_domains'] ) &&
				$file_data['agency_domains'] &&
				is_array( $file_data['agency_domains'] )
			) {
				// Loop through each domain we find.
				foreach ( $file_data['agency_domains'] as $agency_domain ) {
					// Lowercase the domain name.
					$agency_domain = strtolower( $agency_domain );
					// Remove URL Prefixes (if any) and strip all slashes.
					$agency_domain = wp_unslash( str_replace( array( '@', 'http://', 'https://', 'www.' ), '', $agency_domain ) );
					// Add it after sanitizing as a text field.
					$settings['agency_domains'][] = sanitize_text_field( $agency_domain );
				}
			}

			// Get Support Email.
			if ( isset( $file_data['support_email_to'] ) && is_email( $file_data['support_email_to'] ) ) {
				$settings['support_email_to'] = $file_data['support_email_to'];
			}

			// Get Managed By Name and URL.
			if ( isset( $file_data['managed_by_name'] ) && isset( $file_data['managed_by_url'] ) && wp_http_validate_url( $file_data['managed_by_url'] ) ) {
				$settings['managed_by_name'] = sanitize_text_field( $file_data['managed_by_name'] );
				$settings['managed_by_url']  = esc_url_raw( strtolower( $file_data['managed_by_url'] ) );
			}

			// Set Site Admin Email.
			if ( isset( $file_data['site_admin_email'] ) ) {
				self::set_site_admin_email( $file_data['site_admin_email'] );
			}

			// Is this a fresh WP Install?
			if ( isset( $file_data['is_fresh_wp_install'] ) && $file_data['is_fresh_wp_install'] ) {

				// Setup WP with Default Options.
				self::set_wp_defaults();

				// Load Language Options.
				if ( isset( $file_data['fresh_wp_install_wplang'] ) &&
					$file_data['fresh_wp_install_wplang'] &&
					isset( $file_data['fresh_wp_install_timezone_string'] ) &&
					$file_data['fresh_wp_install_timezone_string']
				) {

					// Update Timezone and Language.
					$wplang          = $file_data['fresh_wp_install_wplang'];
					$timezone_string = $file_data['fresh_wp_install_timezone_string'];
					self::set_site_language( $wplang, $timezone_string );
				}
			}

			// Check if we need to save anything.
			if ( $settings ) {
				// Update DB with Autoload as false.
				update_option( 'wlds_options_extra', $settings, false );
			}
		}
	}
}
