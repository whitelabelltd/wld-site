<?php
/**
 * Admin
 *
 * @package wld-site
 */

namespace WLDS\Module;

use WLDS\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Admin Class for WP Admin
 */
class Admin extends Modules {

	/**
	 * Holds the Plugin Basename
	 *
	 * @var string holds the basename of the plugin.
	 */
	protected $plugin_basename;

	/**
	 * Constructor
	 */
	public function init() {
		// Get Plugin Basename.
		$this->plugin_basename = plugin_basename( trim( WLDS_FILE ) );
	}

	/**
	 * WP Hooks
	 *
	 * @return void
	 */
	public function hooks() {

		// Add Footer Text.
		add_filter( 'admin_footer_text', array( $this, 'filter_admin_footer_text' ) );

		// Add Plugin Action Links.
		add_filter( "plugin_action_links_$this->plugin_basename", array( $this, 'plugin_action_links' ), 10, 4 );
		add_filter( "plugin_action_links_$this->plugin_basename", array( $this, 'plugin_disable_deactivation' ), 15, 4 );

		// Plugin Whitelabel.
		add_action( 'pre_current_active_plugins', array( $this, 'plugin_update_details' ) );

		// Add the JS.
		add_action( 'admin_enqueue_scripts', array( $this, 'add_js' ), 10, 1 );

		// Remove Admin Bar Items.
		add_action( 'wp_before_admin_bar_render', array( $this, 'remove_admin_bar_items' ), 999 );

		// Add CSS.
		add_action( 'admin_enqueue_scripts', array( $this, 'add_css' ), 20, 1 );

		// Show Local Environment Notice.
		add_action( 'admin_notices', array( $this, 'local_environment_notice' ), 25 );
	}

	/**
	 * Show Admin Notice if running locally
	 *
	 * @return void
	 */
	public function local_environment_notice() {
		if ( $this->is_local_env() ) {
			?>
			<div class="notice notice-info">
				<p><?php echo( esc_html_x( 'Running in Local Development Environment', 'Admin Notice for Local Dev Environment', 'wld-site' ) ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Filter admin footer text "Thank you for creating..."
	 *
	 * @param string $text Footer text.
	 * @return string
	 */
	public function filter_admin_footer_text( $text ) {
		// Grab details from options.
		$author_name = $this->get_extra_option( 'managed_by_name' );
		$author_url  = $this->get_extra_option( 'managed_by_url' );
		if ( ! $author_name || ! $author_url ) {
			// Set Defaults.
			$author_name = 'Whitelabel Digital';
			$author_url  = 'https://whitelabel.ltd';
		}

		// Remove the ending dot (if it exists).
		$text_closing_tag = '';
		if ( str_ends_with( $text, '.</span>' ) ) {
			$text             = substr( $text, 0, strlen( $text ) - 8 );
			$text_closing_tag = '</span>';
		}

		// Add text footer to WP Admin.
		$link = sprintf( '<a href="%s" target="_blank">%s</a>', $author_url, esc_html( $author_name ) );
		/* translators: WP Admin footer text to author link */
		$text .= ' ' . sprintf( _x( 'and %s.', 'WP Admin Footer Text before link', 'wld-site' ), $link );

		return $text . $text_closing_tag;
	}

	/**
	 * Update Plugin Details if needed
	 *
	 * @return void
	 */
	public function plugin_update_details() {
		global $wp_list_table;

		// Grab details from options.
		$author_name = $this->get_extra_option( 'managed_by_name' );
		$author_url  = $this->get_extra_option( 'managed_by_url' );

		if ( ! empty( $author_name ) || ! empty( $author_url ) ) {
			// Create Key.
			$key = plugin_basename( WLDS_FILE );

			// If Plugin is set.
			if ( isset( $wp_list_table->items[ $key ] ) ) {
				// Author Name Replace.
				if ( ! empty( $author_url ) ) {
					$wp_list_table->items[ $key ]['Author']     = $author_name;
					$wp_list_table->items[ $key ]['AuthorName'] = $author_name;
				}

				// Author and Plugin URL Replace.
				if ( ! empty( $author_url ) ) {
					$wp_list_table->items[ $key ]['AuthorURI'] = $author_url;
					$wp_list_table->items[ $key ]['PluginURI'] = $author_url;
				}
			}
		}
	}

	/**
	 * Adds items to the plugin's action links on the Plugins listing screen.
	 *
	 * @param array<string,string> $actions     Array of action links.
	 * @param string               $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array                $plugin_data An array of plugin data.
	 * @param string               $context     The plugin context.
	 *
	 * @return array<string,string> Array of action links.
	 */
	public function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		$new = array(
			'wlds-settings' => sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=wlds_option_page' ) ), esc_html_x( 'Settings', 'Plugin Action Link for Plugin Settings', 'wld-site' ) ),
		);

		// Add Help Link if email exists from setting.
		if ( $this->get_extra_option( 'support_email_to' ) ) {
			$new['wlds-help'] = sprintf( '<a href="%s">%s</a>', '#', esc_html_x( 'Help', 'Plugin Action Link for Plugin Help', 'wld-site' ) );
		}

		return array_merge( $new, $actions );
	}

	/**
	 * Disables Plugin Activation for non-agency admins
	 *
	 * @param string[] $actions An array of plugin action links.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins' directory.
	 * @param array    $plugin_data An array of plugin data.
	 * @param string   $context The plugin context. By default, this can include 'all', 'active', 'inactive', 'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 *
	 * @return string[]
	 */
	public function plugin_disable_deactivation( $actions, $plugin_file, $plugin_data, $context ) {
		if ( $this->is_extra_option_enabled( 'disable_plugin_deactivation' ) &&
			! $this->is_agency_member()
		) {
			// Remove deactivate link for platform plugin.
			if ( isset( $actions['deactivate'] ) ) {
				$actions['deactivate'] = sprintf( '<span style="cursor: not-allowed;">%s</span>', esc_html_x( 'Deactivate', 'Plugin Action Link for Plugin Deactivation', 'wld-site' ) );
			}
		}

		return $actions;
	}

	/**
	 * The hook being added into the WP Admin based on which page
	 *
	 * @param string $hook WP Hook.
	 */
	public function add_js( $hook ): void {

		// Set JS Dir Path.
		$path = plugin_dir_url( WLDS_FILE ) . 'assets/js/';

		// Are we on the Plugins page and is the current user not an agency member?
		if ( 'plugins.php' === $hook &&
			$this->is_extra_option_enabled( 'disable_plugin_deactivation' ) &&
			! $this->is_agency_member()
		) {
			// Add JS Script to disable the Checkbox for the specific plugin.
			wp_register_script( 'wlds_plugins', $path . 'wlds-admin-plugins.min.js', array( 'jquery-core' ), WLDS_VERSION, true );
			wp_enqueue_script( 'wlds_plugins' );
			wp_localize_script(
				'wlds_plugins',
				'wlds_p',
				array(
					// Create the ID based on the Plugin Basename using MD5 Hash.
					'checkbox_id' => sprintf( 'checkbox_%s', md5( $this->plugin_basename ) ),
				)
			);
		}
	}

	/**
	 * Removes Admin Bar Items
	 */
	public function remove_admin_bar_items() {

		global $wp_admin_bar;
		if ( ! is_object( $wp_admin_bar ) ) {
			return;
		}

		$slugs_core = apply_filters(
			'wlds_admin_bar_core',
			array(
				'user-actions',
				'user-info',
				'edit-profile',
				'logout',
				'menu-toggle',
				'my-account',
				'top-secondary',
			)
		);

		$slugs = apply_filters(
			'wlds_admin_bar_items',
			array(

				// WP Core Items.
				'wp-logo',
				'about',
				'wporg',
				'documentation',
				'support-forums',
				'feedback',

			/*
			ADMIN BAR ITEMS
			wp-logo : [CORE] WP Logo.
			about : [CORE] About
			wporg : [CORE] WP Organization
			documentation : [CORE] WP Documentation
			support-forums : [CORE] WP Support Forums
			feedback : [CORE] WP Feedback
			site-name : [CORE] Site Name
			view-site : [CORE] View Site
			comments : [CORE] Comments
			new-content : [CORE] New Content Menu
			new-post : [CORE] New Post
			new-media : [CORE] New Media
			new-page : [CORE] New Page
			wp-logo-external : [CORE] External Logo

			wpnt-notes-unread-count : JetPack Notes
			debug-bar : Debug Bar
			w3tc : W3 Cache
			ngg-menu : Next Gen Gallery Menu
			itsec_admin_bar_menu : iThemes Security Menu
			backwpup : Backup-WP Menu
			drift-admin-menu : Drift
			*/
			)
		);

		// Remove all the items on the list.
		if ( is_array( $slugs ) ) {
			foreach ( $slugs as $slug ) {

				// Check if it is on the Core list, in that case skip it.
				if ( ! in_array( $slug, $slugs_core, true ) ) {
					// Check if it starts with 'node-' if so its node not a menu.
					if ( str_starts_with( $slug, 'node-' ) ) {
						$wp_admin_bar->remove_node( substr( $slug, 5 ) );
					} else {
						$wp_admin_bar->remove_menu( $slug );
					}
				}
			}
		}
	}

	/**
	 * Adds the needed CSS
	 *
	 * @return void
	 */
	public function add_css(): void {
		wp_register_style( 'wlds-admin', $this->get_url_css( 'admin.min' ), array(), $this->get_resource_version() );
		wp_enqueue_style( 'wlds-admin' );
	}
}

new Admin();
