<?php
/*
Plugin Name: Wordfence Cloudfront IP Address Updater
Plugin URI: https://github.com/emfluencekc
Description: If you have Cloudfront in front of Wordpress and are using Wordfence, this plugin is for you. Automatically update the proxy IP addresses for Cloudfront in Wordfence, so that Wordfence can correctly identify the end user IP address. Cloudfront updates its list of IP addresses every now and then.
Author: emfluence
Version: 1.0
Author URI: https://emfluence.com
Network: true
Requires at least: 5.0
Requires PHP: 5.6
*/

class Wordfence_Cloudfront_IP_Updater {

	protected $cron_name = 'wfcfipu_cron_hook';

	function __construct() {
		if ( ! wp_next_scheduled( $this->cron_name ) ) {
			wp_schedule_event( time(), 'daily', $this->cron_name );
		}
		add_action($this->cron_name, [$this, 'refresh_ips']);
		register_deactivation_hook( __FILE__, [$this, 'deactivate'] );
	}

	function refresh_ips() {
		if(!defined('WORDFENCE_VERSION')) return;

		//download list of IPs
		$ips = file_get_contents('http://d7uri8nf7uskq.cloudfront.net/tools/list-cloudfront-ips');
		$ips = json_decode($ips, true);
		$ips = implode("\n", array_merge($ips['CLOUDFRONT_GLOBAL_IP_LIST'], $ips['CLOUDFRONT_REGIONAL_EDGE_IP_LIST']));

		global $wpdb;
		$table = wfDB::networkTable('wfConfig');
		$wpdb->query($wpdb->prepare("UPDATE %s SET val = %s WHERE name = 'howGetIPs_trusted_proxies'", $table, $ips));
		wp_cache_delete('alloptions', 'wordfence');
	}

	function deactivate() {
		$timestamp = wp_next_scheduled( $this->cron_name );
		wp_unschedule_event( $timestamp, $this->cron_name );
	}

}

new Wordfence_Cloudfront_IP_Updater();
