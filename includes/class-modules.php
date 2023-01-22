<?php
/**
 * Modules
 *
 * @package wld-site
 */

namespace WLDS;

use Exception;
use WLDS\Helper\Agency;
use WLDS\Helper\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Modules Class
 */
abstract class Modules {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Run Init First.
		$this->init();

		// Load remaining hooks.
		add_action( 'plugins_loaded', array( $this, 'hooks' ) );
	}

	/**
	 * Can be used to run code immediately
	 *
	 * @return void
	 */
	protected function init() {}

	/**
	 * Runs the main hooks
	 *
	 * @return mixed
	 */
	abstract public function hooks();

	/**
	 * Checks if the current user is an agency member
	 *
	 * @param int $user_id WP User ID.
	 * @return bool
	 */
	protected function is_agency_member( $user_id = 0 ) : bool {
		return Agency::is_member( $user_id );
	}

	/**
	 * Gets the Full URL for the JS File
	 *
	 * @param string $js_filename Javascript Filename (without extension).
	 * @return string
	 */
	protected function get_url_js( $js_filename ) {
		return plugin_dir_url( WLDS_FILE ) . 'assets/js/' . basename( $js_filename ) . '.js';
	}

	/**
	 * Gets the Full URL for the CSS File
	 *
	 * @param string $css_filename CSS Filename (without extension).
	 * @return string
	 */
	protected function get_url_css( $css_filename ) {
		return plugin_dir_url( WLDS_FILE ) . 'assets/css/' . basename( $css_filename ) . '.css';
	}

	/**
	 * Gets the local resource version
	 *
	 * @return string
	 */
	protected function get_resource_version() : string {
		if ( $this->is_local_env() ) {
			try {
				return random_int( 10, 1000 );
			} catch ( Exception $e ) {
				return WLDS_VERSION;
			}
		}
		return WLDS_VERSION;
	}

	/**
	 * Are we running in a local environment?
	 *
	 * @return bool
	 */
	protected function is_local_env() : bool {
		return Tools::is_local_env();
	}

	/**
	 * Gets the extra option
	 *
	 * @param string $name Option Name.
	 * @return false|mixed
	 */
	protected function get_extra_option( $name ) {
		return Agency::get_option_extra( $name );
	}

	/**
	 * Is the extra option enabled?
	 *
	 * @param string $name Option Name.
	 * @return bool
	 */
	protected function is_extra_option_enabled( $name ) {
		return Agency::is_option_extra_enabled( $name );
	}

	/**
	 * Is the Setting Enabled?
	 *
	 * @param string $setting_name Setting Name.
	 * @return bool
	 */
	protected function is_setting_enabled( $setting_name = '' ) : bool {
		$value = $this->get_setting( $setting_name );
		if ( 'yes' === $value ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the setting
	 *
	 * @param  string $setting_key Setting key.
	 * @return false|array|string
	 */
	protected function get_setting( $setting_key = null ) {

		// Load Defaults.
		$defaults = apply_filters( 'wlds_options_defaults', array() );
		$settings = get_option( Tools::$option_name );
		$settings = wp_parse_args( $settings, $defaults );

		if ( ! empty( $setting_key ) ) {
			return $settings[ $setting_key ] ?? '';
		}

		return $settings;
	}
}
