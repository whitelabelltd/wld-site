<?php
/**
 * Cloudflare
 *
 * @package wld-site
 */

namespace WLDS\Module\ThirdParty;

use WLDS\Helper\IP;
use WLDS\Modules;
use function is_plugin_active;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Updates the IP Address to the end-user IP when using Cloudflare
 * Checks the Cloudflare IP matches known IP DB before setting IP
 * The known IP DB is updated daily from Cloudflare
 */
class Cloudflare extends Modules {
	/**
	 * Only load when the following plugins are not active
	 * wp-rocket does only do it when their Cloudflare module is activated
	 *
	 * @var array
	 */
	protected $plugins = array(
		'cloudflare/cloudflare.php',
		'proxyflare/proxyflare.php',
	);

	/**
	 * The DB Option Name
	 *
	 * @var string
	 */
	protected $option_name = 'wlds_cloudflare_ips';

	/**
	 * The Cloudflare API Url for getting the latest IPs
	 *
	 * @var string
	 */
	protected $cloudflare_api_url = 'https://api.cloudflare.com/client/v4/ips';

	/**
	 * Cloudflare constructor.
	 */
	public function hooks() {

		// Add Options.
		add_filter( 'wlds_options_sections', array( $this, 'option_add_section' ), 20, 1 );
		add_filter( 'wlds_options_setting_fields', array( $this, 'option_add_fields' ), 10, 1 );
		add_filter( 'wlds_options_defaults', array( $this, 'option_defaults' ), 10, 1 );

		// Set IP.
		add_action( 'init', array( $this, 'maybe_set_real_ip' ), 1 );

		// Setup Cron Job.
		add_action( 'admin_init', array( $this, 'setup_cron' ) );

		// Run Cron Job.
		add_action( 'wlds_update_cloudflare_ips', array( $this, 'update_ips' ) );

		// Add Beacon.
		add_action( 'wp_enqueue_scripts', array( $this, 'beacon' ) );
		add_filter( 'script_loader_tag', array( $this, 'beacon_fix_attribute' ), 10, 3 );
		add_filter( 'script_loader_src', array( $this, 'beacon_js_remove_wp_version' ), 10, 2 );
	}

	/**
	 * Maybe set real IP
	 * checks for certain plugins and conditions
	 *
	 * @return void
	 */
	public function maybe_set_real_ip() : void {

		// Running in a local environment? Bail Early.
		if ( $this->is_local_env() ) {
			return;
		}

		// Check Filter Override.
		if ( ! apply_filters( 'wlds_cloudflare_set_real_ip', true ) ) {
			return;
		}

		$run = true;
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		foreach ( $this->plugins as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				$run = false;
				break;
			}
		}

		if ( $run && $this->wp_rocket_cloudflare_enabled() ) {
			$run = false;
		}

		if ( $run ) {
			$this->set_real_ip();
		}
	}

	/**
	 * Setup daily cron for getting the latest Cloudflare IPs
	 */
	public function setup_cron() {
		if ( ! wp_next_scheduled( 'wlds_update_cloudflare_ips' ) ) {
			wp_schedule_event( time(), 'daily', 'wlds_update_cloudflare_ips' );
		}
	}

	/**
	 * Adds the CloudFlare Beacon Code
	 *
	 * @return void
	 */
	public function beacon() {
		// Make sure its enabled and not running locally.
		if ( ! $this->is_local_env() &&
			$this->is_setting_enabled( 'cloudflare_beacon_enable' )
		) {
			$id = $this->get_setting( 'cloudflare_beacon_id' );
			if ( $id ) {
				// Set Script Parameters.
				$link = 'https://static.cloudflareinsights.com/beacon.min.js';

				// Register and add the inline, requires WordPress 6.3 or higher.
				// phpcs:disable
				wp_register_script( 'cf-beacon', $link, array(), false, array(
						'in_footer' => true,
						'strategy'  => 'defer',
					) );
				// phpcs:enable
				wp_enqueue_script( 'cf-beacon' );
			}
		}
	}

	/**
	 * Adds the CloudFlare Beacon Code Attribute
	 *
	 * @param string $tag Tag.
	 * @param string $handle Handle.
	 * @param string $source Source.
	 *
	 * @return string
	 */
	public function beacon_fix_attribute( $tag, $handle, $source ) {
		// Make sure its enabled and not running locally.
		if ( 'cf-beacon' === $handle && ! $this->is_local_env() && $this->is_setting_enabled( 'cloudflare_beacon_enable' )
		) {
			$id = $this->get_setting( 'cloudflare_beacon_id' );
			if ( $id ) {
				$file     = sprintf( "id='%s-js'", $handle );
				$file_add = sprintf( '{"token": "%s"}', $id );
				$file_add = sprintf( "data-cf-beacon='%s' ", $file_add );
				$tag      = str_replace( $file, $file_add . $file, $tag );
			}
		}
		return $tag;
	}

	/**
	 * Removes the Script version numbers
	 *
	 * @param string $src Script Src.
	 * @param string $handle Handle.
	 *
	 * @return string
	 */
	public function beacon_js_remove_wp_version( $src, $handle ) {
		if ( 'cf-beacon' === $handle ) {
			if ( strpos( $src, 'ver=' ) ) {
				$src = remove_query_arg( 'ver', $src );
			}
		}
		return $src;
	}

	/**
	 * Get Cloudflare IPs from Cloudflare API and saves them in the DB
	 *
	 * @return bool
	 */
	public function update_ips() {

		// Set the API URL.
		$url = $this->cloudflare_api_url;

		// Set API Call Details.
		$args = array(
			'headers' => array(
				'User-Agent' => 'WLDSite/' . WLDS_VERSION . ' ' . site_url(),
			),
		);
		// Make the request.
		$response = wp_safe_remote_get( $url, $args );

		// Check Response.
		if ( ! is_wp_error( $response ) ) {
			$response = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $response['success'] ) && true === $response['success'] ) {
				$ips = array(
					'ipv4'          => $response['result']['ipv4_cidrs'] ?? '',
					'ipv6'          => $response['result']['ipv6_cidrs'] ?? '',
					'_last_updated' => time(),
				);

				if ( $ips ) {
					// Update Option.
					update_option( $this->option_name, $ips );
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Check if WP Rocket has the Cloudflare Module running
	 *
	 * @return bool
	 */
	protected function wp_rocket_cloudflare_enabled() : bool {
		if ( function_exists( 'rocket_set_real_ip_cloudflare' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Set Real IP from CloudFlare
	 *
	 * @source cloudflare.php - https://wordpress.org/plugins/cloudflare/
	 */
	protected function set_real_ip() {

		$is_cf = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] );
		if ( ! $is_cf ) {
			return;
		}

		// only run this logic if the REMOTE_ADDR is populated, to avoid causing notices in CLI mode.
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {

			// Grab the Current Cloudflare Address Range.
			$cf_ips_values = $this->get_ips();
			if ( empty( $cf_ips_values ) ) {
				return;
			}

			// Check if we are getting a IPv4 or IPv6 Address.
			// phpcs:disable
			if ( ! str_contains( $_SERVER['REMOTE_ADDR'], ':' ) ) {
				// phpcs:enable
				$cf_ip_ranges = $cf_ips_values['ipv4'] ?? '';

				// IPv4: Update the REMOTE_ADDR value if the current REMOTE_ADDR value is in the specified range.
				foreach ( $cf_ip_ranges as $range ) {
					// phpcs:disable
					if ( IP::ipv4_in_range( $_SERVER['REMOTE_ADDR'], $range ) ) {
						if ( $_SERVER['HTTP_CF_CONNECTING_IP'] ) {
							$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
						}
						// phpcs:enable
						break;
					}
				}
			} else {

				// IPv6: Update the REMOTE_ADDR value if the current REMOTE_ADDR value is in the specified range.
				$cf_ip_ranges = $cf_ips_values['ipv6'];
				// phpcs:disable
				$ipv6         = IP::get_ipv6_full( $_SERVER['REMOTE_ADDR'] );
				// phpcs:enable
				foreach ( $cf_ip_ranges as $range ) {
					if ( IP::ipv6_in_range( $ipv6, $range ) ) {
						// phpcs:disable
						if ( $_SERVER['HTTP_CF_CONNECTING_IP'] ) {
							$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
							// phpcs:enable
						}
						break;
					}
				}
			}
		}
	}

	/**
	 * Get Cloudflare IPs from the DB
	 *
	 * @return array|false
	 */
	protected function get_ips() : mixed {

		$ips = get_option( $this->option_name );
		if ( $ips ) {
			return $ips;
		}
		return false;
	}

	/**
	 * Set Option Defaults
	 *
	 * @param array $defaults default options.
	 * @return array
	 */
	public function option_defaults( $defaults ) : array {

		$defaults['cloudflare_beacon_enable'] = 'no';
		$defaults['cloudflare_beacon_id']     = '';

		return $defaults;
	}

	/**
	 * Add Option Section
	 *
	 * @param array $sections existing option sections.
	 * @return array
	 */
	public function option_add_section( $sections ) {

		// Google Tag Section.
		$sections['wlds_cloudflare_beacon'] = array(
			'title' => _x( 'Cloudflare Beacon', 'Plugin Setting Section Title', 'wld-site' ),
			'text'  => _x( 'This adds the Cloudflare Beacon tracking code on each page when enabled', 'Plugin Setting Section Description', 'wld-site' ),
		);

		return $sections;
	}

	/**
	 * Adds the setting fields
	 *
	 * @param array $fields option fields.
	 * @return array
	 */
	public function option_add_fields( $fields ) {

		// Section Names.
		$section = 'wlds_cloudflare_beacon';

		// Setting Field - Cloudflare Beacon.
		$fields[] = array(
			'name'            => 'cloudflare_beacon_enable',
			'label'           => _x( 'Enabled', 'Plugin Setting Label', 'wld-site' ),
			'section'         => $section,

			// Callback Arguments.
			'type'            => 'radio',
			'label_radio_yes' => _x( 'Yes', 'Plugin Setting Radio Label', 'wld-site' ),
			'label_radio_no'  => _x( 'No', 'Plugin Setting Radio Label', 'wld-site' ),
		);

		// Setting Field - Cloudflare Beacon ID.
		$fields[] = array(
			'name'    => 'cloudflare_beacon_id',
			'label'   => _x( 'Beacon ID', 'Plugin Setting Label', 'wld-site' ),
			'section' => $section,

			// Callback Arguments.
			'type'    => 'text',
		);

		return $fields;
	}
}
new Cloudflare();
