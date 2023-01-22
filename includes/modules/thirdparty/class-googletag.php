<?php
/**
 * Google Tag
 *
 * @package wld-site
 */

namespace WLDS\Module\ThirdParty;

use WLDS\Helper\Tools;
use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Adds a Google Tag to the Front-End
 */
class GoogleTag extends Modules {

	/**
	 * Holds the Tag ID
	 *
	 * @var string tag id.
	 */
	protected $tag_id;

	/**
	 * Run WP Hooks
	 *
	 * @return void
	 */
	public function hooks() :void {

		// Add Options.
		add_filter( 'wlds_options_sections', array( $this, 'option_add_section' ), 10, 1 );
		add_filter( 'wlds_options_setting_fields', array( $this, 'option_add_fields' ), 10, 1 );
		add_filter( 'wlds_options_defaults', array( $this, 'option_defaults' ), 10, 1 );

		// Running in a local environment? Bail Early.
		if ( $this->is_local_env() ) {
			return;
		}

		// Add to Head.
		add_action( 'wp_head', array( $this, 'google_tag_manager_head' ), 1 );

		// Add to page after Body.
		add_action( 'wp_body_open', array( $this, 'google_tag_manager_body' ) );
	}

	/**
	 * Adds Google Tag Manager code in <head> below the <title>.
	 */
	public function google_tag_manager_head() :void {
		$value = false;
		if ( $this->is_setting_enabled( 'google_tag_enable' ) ) {
			$value = $this->get_setting( 'google_tag_id' );
		}

		if ( ! $value ) {
			return;
		}
		// phpcs:disable
		?>
		<!-- Google Tag Manager -->
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
					new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
				j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
				'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
			})(window,document,'script','dataLayer','<?php echo( esc_attr( $value ) ); ?>');</script>
		<!-- End Google Tag Manager -->
		<?php
		// phpcs:enable
	}

	/**
	 * Adds Google Tag Manager code immediately after the opening <body> tag.
	 */
	public function google_tag_manager_body() : void {
		$value = false;
		if ( $this->is_setting_enabled( 'google_tag_enable' ) ) {
			$value = $this->get_setting( 'google_tag_id' );
		}

		if ( ! $value ) {
			return;
		}
		// phpcs:disable
		?>
		<!-- Google Tag Manager (noscript) -->
		<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo( esc_attr( $value ) ); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
		<!-- End Google Tag Manager (noscript) -->
		<?php
		// phpcs:enable
	}

	/**
	 * Set Option Defaults
	 *
	 * @param array $defaults default options.
	 * @return array
	 */
	public function option_defaults( $defaults ) : array {

		$defaults['google_tag_enable'] = 'no';
		$defaults['google_tag_id']     = '';

		return $defaults;
	}

	/**
	 * Add Option Section
	 *
	 * @param array $sections options sections.
	 * @return array
	 */
	public function option_add_section( $sections ) {

		// Google Tag Section.
		$sections['wlds_google_tag'] = array(
			'title' => _x( 'Google Tag ID', 'Plugin Setting Section Title', 'wld-site' ),
			'text'  => _x( 'This adds the Google Tag tracking code on each page when enabled', 'Plugin Setting Section Description', 'wld-site' ),
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
		$section = 'wlds_google_tag';

		// Setting Field - Google Tag.
		$fields[] = array(
			'name'            => 'google_tag_enable',
			'label'           => _x( 'Enabled', 'Plugin Setting Label', 'wld-site' ),
			'section'         => $section,

			// Callback Arguments.
			'type'            => 'radio',
			'label_radio_yes' => _x( 'Yes', 'Plugin Setting Radio Label', 'wld-site' ),
			'label_radio_no'  => _x( 'No', 'Plugin Setting Radio Label', 'wld-site' ),
		);

		// Setting Field - Google Tag ID.
		$fields[] = array(
			'name'    => 'google_tag_id',
			'label'   => _x( 'Google Tag ID', 'Plugin Setting Label', 'wld-site' ),
			'section' => $section,

			// Callback Arguments.
			'type'    => 'text',
		);

		return $fields;
	}
}
new GoogleTag();
