<?php
/*
Plugin Name: Wordfence Cloudfront Proxy IP Addresses
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
	protected $last_name_option_name = 'wfcfipu_last_ran';
	protected $cloudfront_ip_addresses_url = 'http://d7uri8nf7uskq.cloudfront.net/tools/list-cloudfront-ips';

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
		if(!defined('WORDFENCE_VERSION')) return false;

		//download list of IPs
		$ips = file_get_contents($this->cloudfront_ip_addresses_url);
		if(false === $ips) {
			return false;
		}
		$ips = json_decode($ips, true);
		if(empty($ips)) {
			return false;
		}
		$ips = implode("\n", array_merge($ips['CLOUDFRONT_GLOBAL_IP_LIST'], $ips['CLOUDFRONT_REGIONAL_EDGE_IP_LIST']));

		global $wpdb;
		$table = wfDB::networkTable('wfConfig');
		$updated = $wpdb->query($wpdb->prepare("UPDATE $table SET val = %s WHERE name = 'howGetIPs_trusted_proxies'", $ips));
		if(false === $updated) {
			return false;
		}
		wp_cache_delete('alloptions', 'wordfence');
		return (bool) update_option($this->last_name_option_name, time(), false);
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
		if(!defined('WORDFENCE_VERSION')) {
			_e('<div class="notice notice-error"><p><i>Wordfence Cloudfront Proxy IP Addresses</i> requires <i>Wordfence</i> to be active</p></div>');
			return;
		}
		if(false === get_option($this->last_name_option_name, false)) {
			_e('<div class="notice notice-error"><p><i>Wordfence Cloudfront Proxy IP Addresses</i> has not yet been able to get Cloudfront proxy IP addresses</p></div>');
		}
	}

	/**
	 * Add some basic reporting on the admin plugins page, since we don't have a settings page of our own
	 */
	function plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status) {
		if('wordfence-cloudfront-ips/plugin.php' !== $plugin_file) return;
		$last_ran = get_option($this->last_name_option_name, false);
		if(false === $last_ran) {
			$plugin_meta[] = __('Has not yet run');
		} else {
			$plugin_meta[] = sprintf(__('Last ran %s ago'), human_time_diff($last_ran));
		}
		return $plugin_meta;
	}

}

new Wordfence_Cloudfront_IP_Updater();
