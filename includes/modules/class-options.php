<?php
/**
 * Options
 *
 * @package wld-site
 */

namespace WLDS\Module;

use WLDS\Helper\Tools;
use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Options Class
 */
class Options extends Modules {

	/**
	 * Holds the option name
	 *
	 * @var string Option Name
	 */
	protected $option_name;

	/**
	 * Holds the page slug for the options
	 *
	 * @var string
	 */
	protected $option_page_slug = 'wlds_option_page';

	/**
	 * Init Hook
	 *
	 * @inheritDoc
	 */
	public function hooks() {

		// Check if we are showing the options page.
		if ( apply_filters( 'wlds_options_page_show', true ) ) {

			// Register the settings.
			add_action( 'admin_init', array( $this, 'register_settings' ) );

			// Add Setting Page.
			add_action( 'admin_menu', array( $this, 'add_settings_subpage_menu' ) );
		}
	}

	/**
	 * Set the Option Name
	 *
	 * @return void
	 */
	public function init() {
		$this->option_name = Tools::$option_name;
	}

	/**
	 * Register settings
	 */
	public function register_settings() : void {

		// Slug for the Options Page.
		$options_page = $this->option_page_slug;

		// Register Setting Group.
		register_setting(
			$options_page,
			$this->option_name,
			array(
				'sanitize_callback' => array( $this, 'sanitize_fields' ),
			)
		);

		// Defaults for the Section.
		$section_defaults = array(
			'title'           => '',
			'text'            => '',

			// Custom Callback Function (optional).
			'callback_custom' => '',
		);

		// Add any needed sections.
		$sections = apply_filters( 'wlds_options_sections', array() );
		if ( $sections ) {
			foreach ( $sections as $section_name => $section_data ) {

				// Set Defaults.
				$section_data = wp_parse_args( $section_data, $section_defaults );

				// Set Text.
				$args = array(
					'text' => $section_data['text'],
				);

				// Set Callback.
				$callback = array( $this, 'section_description' );
				if ( $section_data['callback_custom'] ?? false ) {
					$callback = $section_data['callback_custom'];
				}

				if ( $section_name && $section_data['title'] ) {
					// Add Section.
					add_settings_section(
						$section_name,
						esc_html( $section_data['title'] ),
						$callback,
						$options_page,
						$args
					);
				}
			}
		}

		// Defaults for the Setting Fields.
		$setting_field_defaults = array(
			'name'            => '',
			'label'           => '',
			'section'         => '',

			// Callback Arguments.
			'type'            => 'text',
			'label_radio_yes' => '',
			'label_radio_no'  => '',

			// Custom Callback Function (optional).
			'callback_custom' => '',
		);

		// Add Setting Fields.
		$setting_fields = apply_filters( 'wlds_options_setting_fields', array() );
		if ( $setting_fields ) {
			foreach ( $setting_fields as $setting_field_options ) {

				// Set Defaults.
				$setting_field_options = wp_parse_args( $setting_field_options, $setting_field_defaults );

				// Make sure we have the required fields.
				if ( $setting_field_options['name'] &&
					$setting_field_options['label'] &&
					$setting_field_options['section']
				) {

					// Get Field Type.
					$type = $setting_field_options['type'] ?? 'text';

					// Add Callback Arguments.
					$callback_args = array(
						'type'            => $type,
						'label_radio_yes' => $setting_field_options['label_radio_yes'] ?? '',
						'label_radio_no'  => $setting_field_options['label_radio_no'] ?? '',
						'option_name'     => $setting_field_options['name'],
					);

					// Set sanitation callback based on type.
					switch ( $type ) {
						case 'radio':
							$this->add_sanitation_radio_field( $setting_field_options['name'] );
							break;

						// Default to Text Based sanitising functions.
						default:
						case 'text':
							$this->add_sanitation_text_field( $setting_field_options['name'] );
							break;
					}

					// Set Callback.
					$callback = array( $this, 'callback_general' );
					if ( $setting_field_options['callback_custom'] ?? false ) {
						$callback = $setting_field_options['callback_custom'];
					}

					// Add the field.
					add_settings_field(
						$setting_field_options['name'],
						esc_html( $setting_field_options['label'] ),
						$callback,
						$options_page,
						$setting_field_options['section'],
						$callback_args
					);
				}
			}
		}
	}

	/**
	 * Adds the field to use the radio sanitize function
	 *
	 * @param string $field_name field name.
	 *
	 * @return void
	 */
	protected function add_sanitation_radio_field( $field_name ) {
		add_filter(
			'wlds_option_radio_fields',
			function ( $fields ) use ( $field_name ) {
				$fields[ $field_name ] = true;
				return $fields;
			}
		);
	}

	/**
	 * Adds the field to use the text sanitize function
	 *
	 * @param string $field_name field name.
	 *
	 * @return void
	 */
	protected function add_sanitation_text_field( $field_name ) {
		add_filter(
			'wlds_option_text_fields',
			function ( $fields ) use ( $field_name ) {
				$fields[ $field_name ] = true;
				return $fields;
			}
		);
	}

	/**
	 * General Callback
	 *
	 * @param array $args Options.
	 * @return void
	 */
	public function callback_general( $args ) {
		$type        = $args['type'] ?? 'text';
		$option_name = $args['option_name'] ?? null;
		if ( is_null( $option_name ) ) {
			return;
		}

		if ( 'bool' === $type || 'radio' === $type ) {
			$this->field_bool( $option_name, $args['label_radio_yes'], $args['label_radio_no'] );
			return;
		}

		if ( 'text' === $type ) {
			$this->field_text( $option_name );
		}
	}

	/**
	 * Sanitises the options fields
	 *
	 * @param array $fields the fields.
	 *
	 * @return array
	 */
	public function sanitize_fields( $fields ) {
		if ( $fields ) {

			// Get Filter Data.
			$radio_fields = apply_filters( 'wlds_option_radio_fields', array() );
			$text_fields  = apply_filters( 'wlds_option_text_fields', array() );

			// Loop through field.
			foreach ( $fields as $name => $value ) {

				// Apply Radio field sanitization.
				if ( $radio_fields && isset( $radio_fields[ $name ] ) ) {
					$fields[ $name ] = $this->sanitize_radio_field( $value );
				}

				// Apply Text field sanitization.
				if ( $text_fields && isset( $text_fields[ $name ] ) ) {
					$fields[ $name ] = $this->sanitize_text_field( $value );
				}

				// Apply Per field name filter.
				$fields[ $name ] = apply_filters( "wlds_option_sanitize_$name", $fields[ $name ] );
			}
		}
		return $fields;
	}

	/**
	 * Sanitize Radio Field
	 *
	 * @param string $value field value.
	 * @return false
	 */
	protected function sanitize_radio_field( $value ) {
		if ( empty( $value ) || 'yes' === $value || 'no' === $value ) {
			return $value;
		}
		return false;
	}

	/**
	 * Sanitize Text Field
	 *
	 * @param string $value field value.
	 * @return string
	 */
	protected function sanitize_text_field( $value ) {
		return sanitize_text_field( $value );
	}

	/**
	 * Adds a subpage to the settings menu
	 *
	 * @return void
	 */
	public function add_settings_subpage_menu() {
		add_options_page(
			_x( 'Platform Options', 'Page Menu Title', 'wld-site' ),
			_x( 'Platform', 'Settings Menu Title', 'wld-site' ),
			'manage_options',
			$this->option_page_slug,
			array( $this, 'add_settings_subpage' )
		);
	}

	/**
	 * Adds the Settings Page
	 *
	 * @return void
	 */
	public function add_settings_subpage() {

		?>
		<div class='wrap'>
			<?php echo( sprintf( '<h1>%s</h1>', esc_html_x( 'Platform Options', 'Plugin Options Page Title', 'wld-site' ) ) ); ?>
			<form method='post' action='options.php'>
				<?php
				/* 'option_group' must match 'option_group' from register_setting call */
				settings_fields( $this->option_page_slug );
				do_settings_sections( $this->option_page_slug );
				?>
				<p class='submit'>
					<input name='submit' type='submit' id='submit' class='button-primary' value='<?php echo( esc_html_x( 'Save Changes', 'Plugin Options Save Button', 'wld-site' ) ); ?>' />
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Creates a Radio Field Selection with two options
	 *
	 * @param string $option_name Option Name.
	 * @param string $text_enabled Optional, defaults to Yes.
	 * @param string $text_disabled Optional, defaults to No.
	 *
	 * @return void
	 */
	protected function field_bool( $option_name = '', $text_enabled = '', $text_disabled = '' ) {
		if ( $option_name ) {
			$value = $this->get_setting( $option_name );
			if ( ! $text_enabled ) {
				$text_enabled = esc_html_x( 'Yes', 'Plugin Option Radio Label', 'wld-site' );
			}
			if ( ! $text_disabled ) {
				$text_disabled = esc_html_x( 'No', 'Plugin Option Radio Label', 'wld-site' );
			}
			$field_id = sprintf( 'field-%s-enable-', sanitize_title( $option_name ) );
			?>
			<input name="<?php echo( esc_attr( $this->option_name ) ); ?>[<?php echo( esc_attr( $option_name ) ); ?>]" <?php checked( 'yes', $value ); ?> type="radio" id="<?php echo( esc_attr( $field_id ) ); ?>yes" value="yes"> <label for="<?php echo( esc_attr( $field_id ) ); ?>yes"><?php echo( esc_html( $text_enabled ) ); ?></label><br>
			<input name="<?php echo( esc_attr( $this->option_name ) ); ?>[<?php echo( esc_attr( $option_name ) ); ?>]" <?php checked( 'no', $value ); ?> type="radio" id="<?php echo( esc_attr( $field_id ) ); ?>no" value="no"> <label for="<?php echo( esc_attr( $field_id ) ); ?>no"><?php echo( esc_html( $text_disabled ) ); ?></label>
			<?php
		}
	}

	/**
	 * Output Text Field
	 *
	 * @param string $option_name Option Name.
	 */
	public function field_text( $option_name = '' ) {
		$value    = $this->get_setting( $option_name );
		$field_id = sprintf( 'field-%s-', sanitize_title( $option_name ) );
		?>
		<input name="<?php echo( esc_attr( $this->option_name ) ); ?>[<?php echo( esc_attr( $option_name ) ); ?>]" type="text" id="<?php echo( esc_attr( $field_id ) ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" autocomplete="off">
		<?php
	}

	/**
	 * Output setting section description
	 *
	 * @param array $args Options.
	 */
	public function section_description( $args ) {
		if ( isset( $args['text'] ) && $args['text'] ) {
			echo( sprintf( '<p>%s</p>', esc_html( $args['text'] ) ) );
		}
	}
}
new Options();
