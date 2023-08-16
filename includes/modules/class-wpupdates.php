<?php
/**
 * WP Updates
 *
 * @package wld-site
 */

namespace WLDS\Module;

use WLDS\Modules;
use function wp_safe_redirect;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Authors class
 */
class WPUpdates extends Modules {

	/**
	 * Setup module
	 */
	public function hooks() {
		if ( $this->is_setting_enabled( 'wp_update_translations' ) ) {
			// Enable Auto Updates of Translations.
			add_filter( 'auto_update_translation', '__return_true' );
		}

		// Add Options.
		add_filter( 'wlds_options_sections', array( $this, 'option_add_section' ), 15, 1 );
		add_filter( 'wlds_options_setting_fields', array( $this, 'option_add_fields' ), 10, 1 );
		add_filter( 'wlds_options_defaults', array( $this, 'option_defaults' ), 10, 1 );
	}

	/**
	 * Set Option Defaults
	 *
	 * @param array $defaults Option defaults.
	 * @return array
	 */
	public function option_defaults( $defaults ) : array {
		$defaults['wp_update_translations'] = 'yes';
		return $defaults;
	}

	/**
	 * Add Option Section
	 *
	 * @param array $sections Option Sections.
	 * @return array
	 */
	public function option_add_section( $sections ) {

		// Section.
		$sections['wlds_wp_update'] = array(
			'title' => _x( 'WordPress Auto Updates', 'Plugin Setting Section Title', 'wld-site' ),
			'text'  => _x( 'Allow auto updates to occur for the following items', 'Plugin Setting Section Description', 'wld-site' ),
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
		$section = 'wlds_wp_update';

		// Setting Field - Google Tag.
		$fields[] = array(
			'name'            => 'wp_update_translations',
			'label'           => _x( 'Translations', 'Plugin Setting Label', 'wld-site' ),
			'section'         => $section,

			// Callback Arguments.
			'type'            => 'radio',
			'label_radio_yes' => _x( 'Yes', 'Plugin Setting Radio Label', 'wld-site' ),
			'label_radio_no'  => _x( 'No', 'Plugin Setting Radio Label', 'wld-site' ),
		);

		return $fields;
	}
}
new WPUpdates();
