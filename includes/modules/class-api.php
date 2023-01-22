<?php
/**
 * REST API functionality
 *
 * @package wld-site
 */

namespace WLDS\Module;

use WLDS\Helper\Tools;
use WLDS\Modules;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * REST API customizations class
 */
class Api extends Modules {

	/**
	 * Setup module
	 */
	public function hooks() {

		// Add Options.
		add_filter( 'wlds_options_sections', array( $this, 'option_add_section' ), 15, 1 );
		add_filter( 'wlds_options_setting_fields', array( $this, 'option_add_fields' ), 10, 1 );
		add_filter( 'wlds_options_defaults', array( $this, 'option_defaults' ), 10, 1 );
		add_filter( 'wlds_option_sanitize_rest_api_restrict', array( $this, 'validate_restrict_rest_api_setting' ), 10, 1 );

		// Make sure this runs somewhat late but before core's cookie auth at 100.
		add_filter( 'rest_authentication_errors', array( $this, 'restrict_rest_api' ), 99 );
		add_filter( 'rest_endpoints', array( $this, 'restrict_user_endpoints' ) );
	}

	/**
	 * Set Option Defaults
	 *
	 * @param array $defaults Option defaults.
	 * @return array
	 */
	public function option_defaults( $defaults ) : array {
		$defaults['rest_api_restrict'] = 'users';
		return $defaults;
	}

	/**
	 * Add Option Section
	 *
	 * @param array $sections Option Sections.
	 * @return array
	 */
	public function option_add_section( $sections ) {

		// Google Tag Section.
		$sections['wlds_rest_api'] = array(
			'title' => _x( 'Rest API Restricted', 'Plugin Setting Section Title', 'wld-site' ),
			'text'  => _x( 'Restrict API Access to non-authenticated users', 'Plugin Setting Section Description', 'wld-site' ),
		);

		return $sections;
	}

	/**
	 * Adds the setting fields
	 *
	 * @param array $fields Option Fields.
	 * @return array
	 */
	public function option_add_fields( $fields ) {

		// Section Names.
		$section = 'wlds_rest_api';

		// Setting Field - Google Tag.
		$fields[] = array(
			'name'            => 'restrict_rest_api',
			'label'           => _x( 'REST API Availability', 'Plugin Setting Label', 'wld-site' ),
			'section'         => $section,

			// Callback Arguments.
			'callback_custom' => array( $this, 'restrict_rest_api_ui' ),
		);

		return $fields;
	}

	/**
	 * Return a 403 status and corresponding error for un-authorised REST API access.
	 *
	 * @param WP_Error|null|bool $result  Error from another authentication handler,
	 *                                    null if we should handle it, or another value
	 *                                    if not.
	 *
	 * @return WP_Error|null|bool
	 */
	public function restrict_rest_api( $result ) {
		// Respect other handlers.
		if ( null !== $result ) {
			return $result;
		}

		$restrict = $this->get_setting( 'restrict_rest_api' );

		if ( 'all' === $restrict && ! $this->user_can_access_rest_api() ) {
			return new WP_Error( 'rest_api_restricted', esc_html_x( 'Authentication Required', 'Error Text when Rest API Authentication is required', 'wld-site' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return null;
	}

	/**
	 * Remove user endpoints for un-authenticated users.
	 *
	 * @param  array $endpoints Array of endpoints.
	 * @return array
	 */
	public function restrict_user_endpoints( $endpoints ) {
		$restrict = $this->get_setting( 'restrict_rest_api' );

		if ( 'none' === $restrict ) {
			return $endpoints;
		}

		if ( ! $this->user_can_access_rest_api() ) {
			$keys = preg_grep( '/\/wp\/v2\/users\b/', array_keys( $endpoints ) );

			foreach ( $keys as $key ) {
				unset( $endpoints[ $key ] );
			}

			return $endpoints;
		}

		return $endpoints;
	}

	/**
	 * Display UI for restrict REST API setting.
	 *
	 * @return void
	 */
	public function restrict_rest_api_ui() {
		$option_name = 'rest_api_restrict';
		$restrict    = $this->get_setting( $option_name );

		?>
		<fieldset>
			<legend class="screen-reader-text"><?php echo( esc_html( _x( 'REST API Availability', 'Option Radio Label', 'wld-site' ) ) ); ?></legend>
			<p><label for="restrict-rest-api-all">
				<input id="restrict-rest-api-all" name="<?php echo( esc_attr( Tools::$option_name ) ); ?>[<?php echo( esc_attr( $option_name ) ); ?>]" type="radio" value="all"<?php checked( $restrict, 'all' ); ?> />
				<?php echo( esc_html( _x( 'Restrict all access to authenticated users', 'Option Radio Label', 'wld-site' ) ) ); ?>
			</label></p>
			<p><label for="restrict-rest-api-users">
				<input id="restrict-rest-api-users" name="<?php echo( esc_attr( Tools::$option_name ) ); ?>[<?php echo( esc_attr( $option_name ) ); ?>]" type="radio" value="users"<?php checked( $restrict, 'users' ); ?> />
				<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s is a link to the developer reference for the users endpoint. */
							_x( "Restrict access to the <code><a href='%s'>users</a></code> endpoint to authenticated users", 'Option Radio Label', 'wld-site' ),
							esc_url( 'https://developer.wordpress.org/rest-api/reference/users/' )
						)
					);
				?>
			</label></p>
			<p><label for="restrict-rest-api-n">
				<input id="restrict-rest-api-n" name="<?php echo( esc_attr( Tools::$option_name ) ); ?>[<?php echo( esc_attr( $option_name ) ); ?>]" type="radio" value="none"<?php checked( $restrict, 'none' ); ?> />
				<?php echo( esc_html( _x( 'Publicly accessible', 'Option Radio Label', 'wld-site' ) ) ); ?>
			</label></p>
		</fieldset>
		<?php
	}

	/**
	 * Check if user can access REST API based on our criteria
	 *
	 * @param  int $user_id User ID.
	 * @return bool         Whether the given user can access the REST API.
	 */
	public function user_can_access_rest_api( $user_id = 0 ) {
		return is_user_logged_in();
	}

	/**
	 * Sanitize the setting.
	 *
	 * @param  string $value Current restriction.
	 * @return string
	 */
	public function validate_restrict_rest_api_setting( $value ) {
		if ( in_array( $value, array( 'all', 'users', 'none' ), true ) ) {
			return $value;
		}

		// Default to 'users' in case something wrong gets sent.
		return 'users';
	}
}
new Api();
