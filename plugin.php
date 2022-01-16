<?php
/*
Plugin Name: Wordfence Cloudfront Proxy IP Address Updater
Plugin URI: https://github.com/emfluencekc
Description: If you have Cloudfront in front of Wordpress and are using Wordfence, this plugin is for you. Automatically update the proxy IP addresses for Cloudfront in Wordfence, so that Wordfence can correctly identify the end user IP address. Cloudfront updates its list of IP addresses every now and then.
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
	protected $cloudfront_ip_addresses_url = 'http://d7uri8nf7uskq.cloudfront.net/tools/list-cloudfront-ips';

	function __construct() {
		if ( ! wp_next_scheduled( $this->cron_name ) ) {
			wp_schedule_event( time(), 'daily', $this->cron_name );
		}
		add_action($this->cron_name, [$this, 'refresh_ips']);
		register_activation_hook(__FILE__, [$this, 'refresh_ips']);
		register_deactivation_hook( __FILE__, [$this, 'deactivate'] );
		add_action('admin_notices', [$this, 'check_wordfence_installed']);
	}

	function refresh_ips() {
		if(!defined('WORDFENCE_VERSION')) return;

		//download list of IPs
		$ips = file_get_contents($this->cloudfront_ip_addresses_url);
		$ips = json_decode($ips, true);
		$ips = implode("\n", array_merge($ips['CLOUDFRONT_GLOBAL_IP_LIST'], $ips['CLOUDFRONT_REGIONAL_EDGE_IP_LIST']));

		global $wpdb;
		$table = wfDB::networkTable('wfConfig');
		$wpdb->query($wpdb->prepare("UPDATE %s SET val = %s WHERE name = 'howGetIPs_trusted_proxies'", $table, $ips));
		wp_cache_delete('alloptions', 'wordfence');
		update_option('wfcfipu_last_ran', time(), false);
	}

	function deactivate() {
		$timestamp = wp_next_scheduled( $this->cron_name );
		wp_unschedule_event( $timestamp, $this->cron_name );
	}

	function check_wordfence_installed() {
		if(!defined('WORDFENCE_VERSION')) {
			_e('<div class="notice notice-error"><p><i>Wordfence Cloudfront Proxy IP Address Updater</i> requires <i>Wordfence</i> to be installed</p></div>');
			return;
		}
		if(false === get_option('wfcfipu_last_ran', false)) {
			_e('<div class="notice notice-error"><p><i>Wordfence Cloudfront Proxy IP Address Updater</i> has not yet been able to get Cloudfront proxy IP addresses</p></div>');
		}
	}

}

new Wordfence_Cloudfront_IP_Updater();
