<?php
/**
 * Security
 *
 * @package wld-site
 */

namespace WLDS\Module;

use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Security Class
 */
class Security extends Modules {

	/**
	 * Constructor
	 */
	public function init() {
		// Disable File Editor.
		$this->disable_file_editors();
	}

	/**
	 * WP Hooks
	 *
	 * @return void
	 */
	public function hooks() {

		// Add Options.
		add_filter( 'wlds_options_sections', array( $this, 'option_add_section' ), 15, 1 );
		add_filter( 'wlds_options_setting_fields', array( $this, 'option_add_fields' ), 10, 1 );
		add_filter( 'wlds_options_defaults', array( $this, 'option_defaults' ), 10, 1 );

		// Disable Version in RSS Feed.
		add_filter( 'the_generator', '__return_false' );

		// Custom Generator.
		remove_action( 'wp_head', 'wp_generator' );
		add_action( 'wp_head', array( $this, 'custom_wp_generator' ) );

		// Disable XML-RPC.
		if ( $this->is_setting_enabled( 'disable_xmlrpc' ) ) {
			$this->disable_xmlrpc();
		}
	}

	/**
	 * Disable XML-RPC completely
	 *
	 * @return void
	 */
	protected function disable_xmlrpc() {
		// Disables Un-Authenticated Calls.
		add_filter( 'xmlrpc_enabled', '__return_false' );

		// Disables all Methods.
		add_filter( 'xmlrpc_methods', '__return_empty_array' );

		// Returns a 404-page if loaded.
		add_action( 'wp_loaded', array( $this, 'xmlrpc_return_404' ) );
	}

	/**
	 * Returns a 404 when trying to load XML-RPC.php
	 *
	 * @return void
	 */
	public function xmlrpc_return_404() : void {
		// Check if we are trying to load the page.
		// phpcs:disable
		if ( str_contains( strtolower( $_SERVER['REQUEST_URI'] ), 'xmlrpc.php' ) ) {
			// phpcs:enable

			// Set 404 Headers.
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();

			// Done.
			exit();
		}
	}

	/**
	 * Disables File Editor
	 *
	 * @return void
	 */
	protected function disable_file_editors() {
		if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
			define( 'DISALLOW_FILE_EDIT', true );
		}
	}

	/**
	 * Changes the generator output for WordPress Version
	 */
	public function custom_wp_generator() {
		/* translators: meta_tag generator Description */
		echo '<meta name="generator" content="' . esc_attr( _x( 'WordPress Managed Version', 'Shown in HTML <head> instead of WordPress Version X.X', 'wld-site' ) ) . '" />' . "\n";
	}

	/**
	 * Add Option Section
	 *
	 * @param array $sections Option Sections.
	 * @return array
	 */
	public function option_add_section( $sections ) {
		$sections['wlds_security'] = array(
			'title' => _x( 'Security', 'Plugin Setting Section Title', 'wld-site' ),
			'text'  => _x( 'Manage Security related options', 'Plugin Setting Section Description', 'wld-site' ),
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
		$section = 'wlds_security';

		// Setting Field - WP Embeds.
		$fields[] = array(
			'name'            => 'disable_xmlrpc',
			'label'           => _x( 'XML-RPC', 'Plugin Setting Label', 'wld-site' ),
			'section'         => $section,

			// Callback Arguments.
			'type'            => 'radio',
			'label_radio_yes' => _x( 'Disabled', 'Plugin Setting Radio Label', 'wld-site' ),
			'label_radio_no'  => _x( 'Leave On', 'Plugin Setting Radio Label', 'wld-site' ),
		);

		return $fields;

	}

	/**
	 * Set Option Defaults
	 *
	 * @param array $defaults Option Defaults.
	 * @return array
	 */
	public function option_defaults( $defaults ) : array {

		$defaults['disable_xmlrpc'] = 'yes';

		return $defaults;
	}
}
new Security();
