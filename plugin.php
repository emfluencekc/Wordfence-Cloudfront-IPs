<?php
/*
Plugin Name: Proxy IP Addresses for Cloudfront with Wordfence
Plugin URI: https://github.com/emfluencekc/Wordfence-Cloudfront-IPs
Description: Automatically add and update the proxy IP addresses for Cloudfront in Wordfence.
Author: emfluence
Version: 1.0
Author URI: https://emfluence.com
Network: true
Requires at least: 5.0
Requires PHP: 5.6
License: GPLv2 or later
*/

class Wordfence_Cloudfront_IP_Updater {

	protected $cron_name = 'wfcfipu_cron_hook';
	protected $last_run_option_name = 'wfcfipu_last_ran';
	protected $last_error_option_name = 'wfcfipu_last_error';

	/**
	 * @see https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/LocationsOfEdgeServers.html
	 */
	protected $cloudfront_ip_addresses_url = 'https://ip-ranges.amazonaws.com/ip-ranges.json';

	function __construct() {
		if ( ! wp_next_scheduled( $this->cron_name ) ) {
			wp_schedule_event( time(), 'daily', $this->cron_name );
		}
		add_action($this->cron_name, [$this, 'cron']);
		register_activation_hook(__FILE__, [$this, 'activate']);
		register_deactivation_hook( __FILE__, [$this, 'deactivate'] );
		add_action('admin_notices', [$this, 'admin_notices']);
		add_action('network_admin_notices', [$this, 'admin_notices']);
		add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 4);
	}

	/**
	 * @return bool
	 */
	protected function refresh_ips() {
		if(!defined('WORDFENCE_VERSION')) {
			update_option($this->last_error_option_name, 'Wordfence was not active. If you have since enabled Wordfence, please deactivate and reactivate this plugin.', false);
			return false;
		}

		// Download list of IPs
		$ips = wp_remote_get($this->cloudfront_ip_addresses_url);
		if(is_wp_error($ips)) {
			update_option($this->last_error_option_name, 'Unable to connect to Cloudfront. ' . $ips->get_error_message(), false);
			return false;
		} elseif(200 !== $ips['response']['code']) {
			update_option($this->last_error_option_name, 'Unable to connect to Cloudfront. Response code ' . $ips['response']['code'] . '.', false);
			return false;
		}
		$ips = json_decode($ips['body'], true);
		if(empty($ips) || empty($ips['prefixes'])) {
			update_option($this->last_error_option_name, 'Unable to parse response from Cloudfront', false);
			return false;
		}
		$ips = array_map(function($el) { return $el['ip_prefix']; }, $ips['prefixes']);
		$ips = implode("\n", array_unique($ips));

		// Put those IPs into the Wordfence option
		global $wpdb;
		$table = wfDB::networkTable('wfConfig');
		$updated = $wpdb->query($wpdb->prepare("UPDATE %1s SET val = %s WHERE name = 'howGetIPs_trusted_proxies'", $table, $ips));
		if(false === $updated) {
			update_option($this->last_error_option_name, 'Unable to save IP addresses to Wordfence options', false);
			return false;
		}
		wp_cache_delete('alloptions', 'wordfence');
		delete_option($this->last_error_option_name);
		return (bool) update_option($this->last_run_option_name, time(), false);
	}

	function cron() {
		$this->refresh_ips();
	}

	function activate() {
		$this->refresh_ips();
	}

	function deactivate() {
		$timestamp = wp_next_scheduled( $this->cron_name );
		wp_unschedule_event( $timestamp, $this->cron_name );
	}

	function admin_notices() {
		$data = get_plugin_data(__FILE__);
		$plugin_name = $data['Name'];
		if(!defined('WORDFENCE_VERSION')) {
			echo wp_kses_post(sprintf(__('<div class="notice notice-error"><p><i>%s</i> requires <i>Wordfence</i> to be active</p></div>'), $plugin_name));
			return;
		}
		if(false === get_option($this->last_run_option_name, false)) {
			echo wp_kses_post(sprintf(__('<div class="notice notice-error"><p><i>%s</i> has not been able to get Cloudfront proxy IP addresses</p></div>'), $plugin_name));
		}
		if(false !== get_option($this->last_error_option_name, false)) {
			echo wp_kses_post(sprintf(__('<div class="notice notice-error"><p><i>%s</i> error on last run: %s</p></div>'), $plugin_name, __(get_option($this->last_error_option_name))));
		}
	}

	/**
	 * Add some basic reporting on the admin plugins page, since we don't have a settings page of our own
	 */
	function plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status) {
		if('wordfence-cloudfront-ips/plugin.php' !== $plugin_file) return;
		$last_ran = get_option($this->last_run_option_name, false);
		if(false === $last_ran) {
			$plugin_meta[] = __('Has not yet run');
		} else {
			$plugin_meta[] = sprintf(__('Last ran %s ago'), human_time_diff($last_ran));
		}
		return $plugin_meta;
	}

}

new Wordfence_Cloudfront_IP_Updater();
