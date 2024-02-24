<?php
/**
 * Support
 *
 * @package wld-site
 */

namespace WLDS\Module;

use JetBrains\PhpStorm\NoReturn;
use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Support Class
 */
class Support extends Modules {

	/**
	 * What capability does the user require to use the support module
	 *
	 * @var string WP Capability.
	 */
	protected $capability_required = 'edit_others_pages';

	/**
	 * Action Name
	 *
	 * @var string
	 */
	protected $action_name = 'wlds_support_form';

	/**
	 * Sets the limit for the text area
	 *
	 * @var int
	 */
	protected $text_length_limit = 500;

	/**
	 * Init
	 *
	 * @inheritDoc
	 */
	public function hooks() {

		// Bail if no support email has been loaded.
		if ( ! $this->get_extra_option( 'support_email_to' ) ) {
			return;
		}

		// Load CSS and JS.
		add_action( 'admin_enqueue_scripts', array( $this, 'add_js' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_css' ), 10, 1 );
		add_action( 'admin_footer', array( $this, 'modal_generate_html' ) );
		add_action( 'admin_menu', array( $this, 'add_support_link' ) );
		add_action( "wp_ajax_$this->action_name", array( $this, 'handle_form_sending' ) );

		add_action( 'admin_notices', array( $this, 'placeholder_notice' ), 20 );

		// Add the dashboard widget (all users).
		add_action( 'wp_dashboard_setup', array( $this, 'widget' ) );
	}

	/**
	 * Placeholder for WP Admin Notice, can be used by our JS script
	 *
	 * @return void
	 */
	public function placeholder_notice() {
		?><div id="wlds-support-notice-placeholder" class="notice" style="display: none;">&nbsp;</div>
		<?php
	}

	/**
	 * Handles the form submission
	 *
	 * @return void
	 */
	public function handle_form_sending() : void {
		// Check Ajax Action Nonce.
		if ( 1 === check_admin_referer( $this->action_name, 'security' ) ) {

			// Check Permission.
			if ( ! current_user_can( 'edit_others_pages' ) ) {
				$this->send_error( _x( 'Your user does not have permission to lodge a support request', 'Support Form error', 'wld-site' ) );
			}

			// Check if message exists.
			if ( ! isset( $_POST['message'] ) ) {
				$this->send_error( _x( 'Please enter a message', 'Support Form error', 'wld-site' ) );
			}

			// Load Message.
			$user_message = wp_strip_all_tags( sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) );
			if ( ! $user_message ) {
				$this->send_error( _x( 'Please enter a message', 'Support Form error', 'wld-site' ) );
			}

			// Check Length.
			if ( strlen( $user_message ) > $this->text_length_limit ) {
				$this->send_error( _x( 'Message exceeds 500 characters', 'Support Form error', 'wld-site' ) );
			}

			// User Details.
			$user = wp_get_current_user();
			if ( ! $user ) {
				$this->send_error( _x( 'User Error', 'Support Form error', 'wld-site' ) );
			}

			// Grab Needed Details.
			$user_email = $user->user_email;
			$user_name  = $user->user_login;

			$site_name      = get_bloginfo( 'name' );
			$site_url       = get_bloginfo( 'url' );
			$site_version   = get_bloginfo( 'version' );
			$time_formatted = current_time( DATE_ATOM );

			if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				// phpcs:disable
				$ip = wp_unslash( $_SERVER['REMOTE_ADDR'] );
				// phpcs:enable
			} else {
				$ip = '';
			}

			$email_body  = 'New Support Request' . PHP_EOL;
			$email_body .= '==================' . PHP_EOL;
			$email_body .= PHP_EOL;

			$email_body .= 'Message:' . PHP_EOL;
			$email_body .= $user_message . PHP_EOL . PHP_EOL . PHP_EOL;
			$email_body .= sprintf( 'User Email: %s', $user_email ) . PHP_EOL;
			$email_body .= sprintf( 'User Name: %s', $user_name ) . PHP_EOL;

			$email_body .= PHP_EOL;

			$email_body .= '---' . PHP_EOL;
			$email_body .= sprintf( 'Site Name: %s', $site_name ) . PHP_EOL;
			$email_body .= sprintf( 'Site URL: %s', $site_url ) . PHP_EOL;
			$email_body .= sprintf( 'Site Version: %s', $site_version ) . PHP_EOL;
			$email_body .= sprintf( 'Time: %s', $time_formatted ) . PHP_EOL;
			$email_body .= sprintf( 'User IP: %s', $ip ) . PHP_EOL;

			// Check extra options for email.
			$email_to = $this->get_extra_option( 'support_email_to' );
			if ( ! $email_to ) {
				$this->send_error( _x( 'Support Email Data Missing', 'Support Form error', 'wld-site' ) );
			}

			// Check Email To is valid.
			if ( ! is_email( $email_to ) ) {
				$this->send_error( _x( 'Support Email Data Error', 'Support Form error', 'wld-site' ) );
			}

			// Set Reply-To.
			$headers = array(
				sprintf( 'Reply-To: <%s>', $user_email ),
			);

			// Subject.
			$subject = sprintf( 'New Support Request from %s', $user_email );

			// Send Email.
			if ( wp_mail( $email_to, $subject, $email_body, $headers ) ) {
				// Let the user know.
				wp_send_json_success(
					array(
						'message' => _x( 'Support Request Created', 'Support Form success', 'wld-site' ),
					)
				);
			} else {
				$this->send_error( _x( 'Error sending the support request', 'Support Form error', 'wld-site' ) );
			}
		} else {
			$this->send_error( _x( 'Security Error', 'Support Form error', 'wld-site' ) );
		}
	}

	/**
	 * Adds the Support Menu Item
	 *
	 * @return void
	 */
	public function add_support_link() {
		$title = _x( 'Support', 'Support Menu Title', 'wld-site' );
		add_menu_page(
			$title,
			$title,
			$this->capability_required,
			'wlds-support',
			'',
			'dashicons-editor-help',
			3
		);
	}

	/**
	 * Generate the needed HTML
	 */
	public function modal_generate_html() {

		// Get Current Users Email.
		$email = wp_get_current_user()->user_email;

		// Get Support Company Name.
		$support_name = $this->get_extra_option( 'managed_by_name' );
		if ( $support_name ) {
			$support_name = sprintf( '%s ', $support_name );
		} else {
			$support_name = '';
		}

		// Set header text.
		/* translators: Support Form Header text */
		$h1_header_text = sprintf( __( 'Contact %sSupport', 'wld-site' ), $support_name );

		?>
		<div id="support-modal" style="display: none;">
			<h1 id="support-modal-h1" style="height: 30px; margin-bottom: 30px;"><?php echo( esc_html( $h1_header_text ) ); ?></h1>
			<div class="error_message" style="display: none"></div>
			<div id="support-modal-content" class="content_modal wlds_support_form">
				<div class="textwrapper">
					<label for="field_wlds_support_message" id="field_wlds_support_message_label"><?php echo( esc_html_x( 'Your Message', 'Support Form Text Area Title', 'wld-site' ) ); ?></label>
					<p><?php echo( esc_html_x( 'Type your question below and our team will be in contact soon', 'Support Form Text Area Title Subtext', 'wld-site' ) ); ?></p>
					<div class="textwrapper">
						<textarea cols="2" rows="6" maxlength="<?php echo( absint( $this->text_length_limit ) ); ?>" name="wlds_support_message" id="field_wlds_support_message" autocomplete="off" placeholder="<?php echo( esc_html_x( 'How can we help?', 'Support Form text Area Placeholder', 'wld-site' ) ); ?>"></textarea>
					</div>
				</div>
				<div class="details">
					<?php echo( esc_html_x( 'Do not submit passwords or other sensitive information.', 'Support Form footer text', 'wld-site' ) ); ?><br />
					<?php
					/* translators: Displayed in the support modal in the footer */
					echo( sprintf( esc_html_x( 'You will receive any replies on your registered email address (%s).', 'Support Form footer text line 2', 'wld-site' ), esc_html( $email ) ) );
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Adds the needed JS
	 *
	 * @return void
	 */
	public function add_js() : void {
		// Register Tingle JS.
		wp_register_script( 'tingle', $this->get_url_js( 'tingle.min' ), array( 'jquery-core' ), '0.16.0', true );

		// Register and Get the Support JS.
		wp_register_script( 'wlds_admin_support', $this->get_url_js( 'wlds-admin-support.min' ), array( 'jquery-core', 'tingle' ), $this->get_resource_version(), true );
		wp_enqueue_script( 'wlds_admin_support' );

		// Load Vars.
		wp_localize_script(
			'wlds_admin_support',
			'wlds_support',
			array(
				'ajaxurl'                    => admin_url( 'admin-ajax.php' ),
				'timeout'                    => 10000,

				'action'                     => $this->action_name,
				'security'                   => wp_create_nonce( $this->action_name ),

				'field_id_support_message'   => 'field_wlds_support_message',
				'text_button_support_cancel' => esc_html_x( 'Cancel', 'Support Form Button Text Cancel', 'wld-site' ),
				'text_button_support_send'   => esc_html_x( 'Send', 'Support Form Button Text Send', 'wld-site' ),
			)
		);
	}

	/**
	 * Adds the needed CSS
	 *
	 * @return void
	 */
	public function add_css() : void {
		wp_register_style( 'tingle', $this->get_url_css( 'tingle.min' ), array(), '0.16.0' );
		wp_enqueue_style( 'tingle' );
	}

	/**
	 * Adds the widget to the dashboard
	 *
	 * @return void
	 */
	public function widget() {

		$widget_support_id = 'wlds-dashboard-widget';

		/* translators: Dashboard Widget, Header text */
		wp_add_dashboard_widget(
			$widget_support_id,
			_x( 'Welcome', 'Support widget title', 'wld-site' ),
			function() {

				// Default Values.
				$name  = $this->get_extra_option( 'managed_by_name' );
				$url   = $this->get_extra_option( 'managed_by_url' );
				$email = $this->get_extra_option( 'support_email_to' );

				$class = 'toplevel_page_wlds-support';

				$support = '';
				if ( current_user_can( $this->capability_required ) ) {
					$support = sprintf( '<p><a class="button button-primary ' . esc_attr( $class ) . '" href="#">%s</a></p><br>', _x( 'Get Support', 'Support Widget Button text', 'wld-site' ) );
				}

				/* translators: Dashboard Widget, Content text, name, url, email, support button */
				$html = _x( 'Welcome to the administration panel of your Content Management System (CMS), this allows you to manage your own website.</p>If you have a question please contact us using the following contact information:<br><br>%4$sEmail: <a href="mailto:%3$s">%3$s</a> <br>Web: <a href="%2$s" target="_blank">%2$s</a><p>The %1$s team</p>', 'Support Widget HTML Body', 'wld-site' );

				// phpcs:disable
				return printf(
					$html,
					esc_html( $name ),
					esc_attr( $url ),
					esc_html( $email ),
					$support
				);
				// phpcs:enable

			}
		);

		// Force Position.
		global $wp_meta_boxes;
		$normal_dashboard      = $wp_meta_boxes['dashboard']['normal']['core'];
		$example_widget_backup = array( $widget_support_id => $normal_dashboard[ $widget_support_id ] );
		unset( $normal_dashboard[ $widget_support_id ] );
		$sorted_dashboard = array_merge( $example_widget_backup, $normal_dashboard );

		// phpcs:disable
		$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
		// phpcs:enable
	}

	/**
	 * Sends an error message back in JSON form
	 *
	 * @param string $message the error text.
	 * @return void
	 */
	protected function send_error( $message ) {
		// phpcs:disable
		wp_send_json_error(
			array(
				'message' => esc_html( $message ),
			)
		);
		// phpcs:enable
		die();
	}
}
new Support();
