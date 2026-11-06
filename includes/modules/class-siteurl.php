<?php
/**
 * Site URL
 *
 * Prevents non-agency users from changing the WordPress Address (URL)
 * and Site Address (URL).
 *
 * @package wld-site
 */

namespace WLDS\Module;

use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SiteUrl Class
 */
class SiteUrl extends Modules {

	/**
	 * Options that only agency members may change.
	 *
	 * @var string[]
	 */
	protected const LOCKED_OPTIONS = array( 'siteurl', 'home' );

	/**
	 * WP Hooks
	 *
	 * @return void
	 */
	public function hooks(): void {

		// Stop Settings > General (options.php) from saving the URLs.
		add_filter( 'allowed_options', array( $this, 'filter_allowed_options' ) );

		// Block every other write path (REST API, other plugins).
		foreach ( self::LOCKED_OPTIONS as $option ) {
			add_filter( "pre_update_option_{$option}", array( $this, 'filter_pre_update_option' ), 10, 3 );
		}

		// Disable the fields in Settings > General (after the Flywheel module at 50).
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 60, 1 );
	}

	/**
	 * Adds the script that disables the URL fields in Settings > General
	 *
	 * @param string $hook WP Admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ): void {
		if ( 'options-general.php' !== $hook || ! $this->is_locked() ) {
			return;
		}

		// The Flywheel module already locks these fields with the same notice.
		if ( wp_script_is( 'wlds_flywheel_options_general', 'enqueued' ) ) {
			return;
		}

		wp_register_script( 'wlds_siteurl_lock', $this->get_url_js( 'wlds-admin-siteurl.min' ), array(), $this->get_resource_version(), true );
		wp_enqueue_script( 'wlds_siteurl_lock' );
		wp_localize_script(
			'wlds_siteurl_lock',
			'wlds_s',
			array(
				'field_text' => _x( 'This field is managed by your service provider.', 'Shown underneath the Site URL field in Settings>General', 'wld-site' ),
			)
		);
	}

	/**
	 * Is the current user locked out of changing the site URLs?
	 * Only applies to logged-in users, so cron, WP-CLI and migrations still work.
	 *
	 * @return bool
	 */
	protected function is_locked(): bool {
		return function_exists( 'is_user_logged_in' ) && is_user_logged_in() && ! $this->is_agency_member();
	}

	/**
	 * Removes the locked options from the Settings > General allowed list.
	 * Without this, options.php would save an empty value for the disabled fields.
	 *
	 * @param array $allowed_options Allowed options, keyed by option group.
	 * @return array
	 */
	public function filter_allowed_options( $allowed_options ): array {
		if ( ! is_array( $allowed_options ) ) {
			return (array) $allowed_options;
		}

		if ( ! $this->is_locked() || ! isset( $allowed_options['general'] ) || ! is_array( $allowed_options['general'] ) ) {
			return $allowed_options;
		}

		$allowed_options['general'] = array_values( array_diff( $allowed_options['general'], self::LOCKED_OPTIONS ) );

		return $allowed_options;
	}

	/**
	 * Keeps the old value when a locked user tries to change a site URL.
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
}
new SiteUrl();
