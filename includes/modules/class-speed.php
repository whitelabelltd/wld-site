<?php
/**
 * Speed
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
 * Adds a Google Tag to the Front-End
 */
class Speed extends Modules {

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

		// Disable the WP Embeds.
		if ( $this->is_setting_enabled( 'disable_wp_embeds' ) ) {
			add_action( 'wp_footer', array( $this, 'disable_embeds' ) );
		}

		// Disable Pingbacks.
		if ( $this->is_setting_enabled( 'disable_pingbacks' ) ) {
			add_action( 'pre_ping', array( $this, 'disable_self_trackback' ) );
		}

		// Disable Shortlinks.
		if ( $this->is_setting_enabled( 'disable_shortlinks' ) ) {
			add_action( 'init', array( $this, 'disable_shortlinks' ) );
		}

		// Disable RSS.
		if ( $this->is_setting_enabled( 'disable_rss' ) ) {
			add_action( 'init', array( $this, 'disable_rss' ) );
		}

		// Disable the W.ORG Relational Links.
		if ( $this->is_setting_enabled( 'disable_w_org_links' ) ) {
			add_action( 'init', array( $this, 'w_org_relation_remove' ) );
		}

		// Disable the emoji's.
		if ( $this->is_setting_enabled( 'disable_emoji' ) ) {
			add_action( 'init', array( $this, 'disable_emojis' ) );
		}

		// Disable the comments.
		if ( $this->is_setting_enabled( 'disable_comments' ) ) {
			add_action( 'init', array( $this, 'disable_comments' ) );
		}

		// Always Disable Windows Live Writer.
		remove_action( 'wp_head', 'wlwmanifest_link' );

		// Always remove RSD Link.
		remove_action( 'wp_head', 'rsd_link' );
	}

	/**
	 * Set Option Defaults
	 *
	 * @param array $defaults Option Defaults.
	 * @return array
	 */
	public function option_defaults( $defaults ) : array {

		$defaults['disable_wp_embeds']   = 'yes';
		$defaults['disable_pingbacks']   = 'yes';
		$defaults['disable_shortlinks']  = 'yes';
		$defaults['disable_w_org_links'] = 'yes';
		$defaults['disable_emoji']       = 'yes';
		$defaults['disable_comments']    = 'yes';
		$defaults['disable_rss']         = 'yes';

		return $defaults;
	}

	/**
	 * Add Option Section
	 *
	 * @param array $sections Option Sections.
	 * @return array
	 */
	public function option_add_section( $sections ) {

		// Speed Section.
		$sections['wlds_speed'] = array(
			'title' => _x( 'Speed Optimisation', 'Plugin Setting Section Title', 'wld-site' ),
			'text'  => _x( 'This disables features to speed up page loads', 'Plugin Setting Section Description', 'wld-site' ),
		);

		// Comments Section.
		$sections['wlds_comments'] = array(
			'title' => _x( 'WordPress Comments', 'Plugin Setting Section Title', 'wld-site' ),
			'text'  => _x( 'This allows you to disable comments for Page and Post types', 'Plugin Setting Section Description', 'wld-site' ),
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

		// Default Radio Labels.
		$label_radio_yes = _x( 'Disable', 'Plugin Setting Radio Label', 'wld-site' );
		$label_radio_no  = _x( 'Leave On', 'Plugin Setting Radio Label', 'wld-site' );

		// Section Names.
		$section          = 'wlds_speed';
		$section_comments = 'wlds_comments';

		// Setting Field - WP Embeds.
		$fields[] = array(
			'name'            => 'disable_wp_embeds',
			'label'           => _x( 'WP Embeds', 'Plugin Setting Label', 'wld-site' ),
			'section'         => $section,

			// Callback Arguments.
			'type'            => 'radio',
			'label_radio_yes' => $label_radio_yes,
			'label_radio_no'  => $label_radio_no,
		);

		// Setting Field - Pingbacks.
		$fields[] = array(
			'name'            => 'disable_pingbacks',
			'label'           => _x( 'Pingbacks', 'Plugin Setting Label', 'wld-site' ),
			'section'         => $section,

			// Callback Arguments.
			'type'            => 'radio',
			'label_radio_yes' => $label_radio_yes,
			'label_radio_no'  => $label_radio_no,
		);

		// Setting Field - Shortlinks in header.
		$fields[] = array(
			'name'            => 'disable_shortlinks',
			'label'           => _x( 'Shortlinks in Header', 'Plugin Setting Label', 'wld-site' ),
			'section'         => $section,

			// Callback Arguments.
			'type'            => 'radio',
			'label_radio_yes' => $label_radio_yes,
			'label_radio_no'  => $label_radio_no,
		);

		// Setting Field - RSS.
		$fields[] = array(
			'name'            => 'disable_rss',
			'label'           => _x( 'RSS', 'Plugin Setting Label', 'wld-site' ),
			'section'         => $section,

			// Callback Arguments.
			'type'            => 'radio',
			'label_radio_yes' => $label_radio_yes,
			'label_radio_no'  => _x( 'Enable', 'Plugin Setting Radio Label', 'wld-site' ),
		);

		// Setting Field - api.w.org Links.
		$fields[] = array(
			'name'            => 'disable_w_org_links',
			'label'           => _x( 'api.w.org Links', 'Plugin Setting Label', 'wld-site' ),
			'section'         => $section,

			// Callback Arguments.
			'type'            => 'radio',
			'label_radio_yes' => $label_radio_yes,
			'label_radio_no'  => $label_radio_no,
		);

		// Setting Field - WordPress Emoji's.
		$fields[] = array(
			'name'            => 'disable_emoji',
			'label'           => _x( "WordPress Emoji's", 'Plugin Setting Label', 'wld-site' ),
			'section'         => $section,

			// Callback Arguments.
			'type'            => 'radio',
			'label_radio_yes' => $label_radio_yes,
			'label_radio_no'  => $label_radio_no,
		);

		// Setting Field - WordPress Emoji's.
		$fields[] = array(
			'name'            => 'disable_comments',
			'label'           => _x( 'Comments', 'Plugin Setting Label', 'wld-site' ),
			'section'         => $section_comments,

			// Callback Arguments.
			'type'            => 'radio',
			'label_radio_yes' => $label_radio_yes,
			'label_radio_no'  => $label_radio_no,
		);

		return $fields;
	}

	/**
	 * Remove Emoji's from WordPress
	 *
	 * @return void
	 */
	public function disable_emojis() {
		// Disable Emojis.
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', array( $this, 'disable_emojis_tinymce' ) );
	}

	/**
	 * Remove Emoji's from the Tinymce Editor
	 *
	 * @param array $plugins active plugins.
	 * @return array
	 */
	public function disable_emojis_tinymce( $plugins ) {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, array( 'wpemoji' ) );
		} else {
			return array();
		}
	}

	/**
	 * Remove W.ORG Relational Links
	 *
	 * @return void
	 */
	public function w_org_relation_remove() {
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );

	}

	/**
	 * Disables the RSS Feed
	 *
	 * @return void
	 */
	public function disable_rss() : void {
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );

		add_action( 'do_feed', array( $this, 'disable_rss_feed' ), 1 );
		add_action( 'do_feed_rdf', array( $this, 'disable_rss_feed' ), 1 );
		add_action( 'do_feed_rss', array( $this, 'disable_rss_feed' ), 1 );
		add_action( 'do_feed_rss2', array( $this, 'disable_rss_feed' ), 1 );
		add_action( 'do_feed_atom', array( $this, 'disable_rss_feed' ), 1 );
		add_action( 'do_feed_rss2_comments', array( $this, 'disable_rss_feed' ), 1 );
		add_action( 'do_feed_atom_comments', array( $this, 'disable_rss_feed' ), 1 );
	}

	/**
	 * Shows a disabled message for the RSS feed
	 *
	 * @return void
	 */
	public function disable_rss_feed() {
		// phpcs:disable
		wp_die( esc_html( _x( 'No feed available', 'RSS Feed disabled message', 'wld-site' ) ), '', array( 'response' => 404 ) );
		// phpcs:enable
	}

	/**
	 * Removes the WP Embed Scripts
	 *
	 * @return void
	 */
	public function disable_embeds() {
		wp_dequeue_script( 'wp-embed' );
	}

	/**
	 * Disables Self Trackbacks (Pingbacks).
	 *
	 * @param array $links Links.
	 * @return void
	 */
	public function disable_self_trackback( &$links ) {
		foreach ( $links as $l => $link ) {
			if ( str_starts_with( $link, get_option( 'home' ) ) ) {
				unset( $links[ $l ] );
			}
		}
	}

	/**
	 * Removes Shortlinks.
	 *
	 * @return void
	 */
	public function disable_shortlinks() {
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	}

	/**
	 * Disables Comments.
	 *
	 * @return void
	 */
	public function disable_comments() {
		add_filter( 'comments_open', array( $this, 'disable_comments_page_posts' ), 20, 2 );
	}

	/**
	 * Disables Comments for Page and Post Types
	 *
	 * @param bool $open Whether the current post is open for comments.
	 * @param int  $post_id The post ID.
	 *
	 * @return bool
	 */
	public function disable_comments_page_posts( $open, $post_id ) {
		$post_type = get_post_type( $post_id );
		if ( 'post' === $post_type ||
			'page' === $post_type
		) {
			return false;
		}
		return $open;
	}
}
new Speed();
