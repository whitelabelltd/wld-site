<?php
/**
 * Email
 *
 * @package wld-site
 */

namespace WLDS\Module;

use WLDS\Modules;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Email Class
 */
class Email extends Modules {

	/**
	 * Init Hook
	 *
	 * @inheritDoc
	 */
	public function hooks() {
		// Disable successfull plugin auto-update email notifications.
		if ( apply_filters( 'wlds_email_disable_update_success_plugin', true ) ) {
			add_filter(
				'auto_plugin_update_send_email',
				array(
					$this,
					'disable_plugin_theme_update_success_emails',
				),
				10,
				2
			);
		}

		// Disable successfull theme auto-update email notifications.
		if ( apply_filters( 'wlds_email_disable_update_success_theme', true ) ) {
			add_filter(
				'auto_theme_update_send_email',
				array(
					$this,
					'disable_plugin_theme_update_success_emails',
				),
				10,
				2
			);
		}

		// Disable automatic "Your Site Has Been Updated..." emails.
		if ( apply_filters( 'wlds_email_disable_update_success_wp', true ) ) {
			add_filter( 'auto_core_update_send_email', array( $this, 'disable_core_update_success_emails' ), 10, 4 );
		}

		// Disables password change notification emails for non-admin/store-manager users.
		if ( apply_filters( 'wlds_email_disable_update_password', true ) ) {
			add_filter(
				'wp_password_change_notification_email',
				array(
					$this,
					'stop_password_change_notifications_for_customers',
				),
				10,
				3
			);
		}

		// Disables new user notification emails for non-admin users.
		if ( apply_filters( 'wlds_email_disable_update_new_user', true ) ) {
			add_filter(
				'wp_send_new_user_notification_to_admin',
				array(
					$this,
					'disable_new_user_admin_email',
				),
				10,
				2
			);
		}
	}

	/**
	 * Disable New User Registration emails if it is a non-admin user
	 *
	 * @param bool    $send are sending the email.
	 * @param WP_User $user WP User Object.
	 *
	 * @return bool
	 */
	public function disable_new_user_admin_email( $send, $user ) : bool {
		if ( $send &&
			$user &&
			! user_can( $user, 'administrator' )
		) {
			return false;
		}
		return $send;
	}

	/**
	 * Disables successfull theme and plugin update notifications
	 *
	 * @param bool  $enabled are notifications enabled.
	 * @param array $update_results results.
	 *
	 * @return bool
	 */
	public function disable_plugin_theme_update_success_emails( $enabled, $update_results ) {
		// Bail early if not enabled anyway.
		if ( $enabled ) {
			// Loop through each result.
			foreach ( $update_results as $update_result ) {
				// Check for any failed updates.
				if ( true !== $update_result->result ) {
					return $enabled;
				}
			}
			// Don't send notifications for successfull updates.
			return false;
		}
		return $enabled;
	}

	/**
	 * Disables successfull WP Core auto-update emails from being sent
	 *
	 * @param bool   $send Whether to send the email. Default true.
	 * @param string $type The type of email to send. Can be one of 'success', 'fail', 'critical'.
	 * @param object $core_update The update offer that was attempted.
	 * @param mixed  $result The result for the core update. Can be WP_Error.
	 *
	 * @return bool
	 */
	public function disable_core_update_success_emails( $send, $type, $core_update, $result ) {
		if ( ! empty( $type ) &&
			'success' === $type
		) {
			return false;
		}
		return $send;
	}

	/**
	 * Prevents password change email notifications for non-admin or shop managers
	 *
	 * @param array   $wp_password_change_notification_email email address.
	 * @param WP_User $user WP User Object.
	 * @param string  $blogname Site name.
	 *
	 * @return array
	 */
	public function stop_password_change_notifications_for_customers( $wp_password_change_notification_email, $user, $blogname ) {
		if ( user_can( $user, 'administrator' ) ||
			user_can( $user, 'manage_woocommerce' )
		) {
			return $wp_password_change_notification_email;
		}
		// Return Blank value array, preventing the email from being sent.
		return array(
			'to'      => '',
			'subject' => '',
			'message' => '',
			'headers' => array(),
		);
	}
}
new Email();
