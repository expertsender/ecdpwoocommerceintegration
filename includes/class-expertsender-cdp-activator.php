<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    ExpertSender_CDP
 * @subpackage ExpertSender_CDP/includes
 * @author     Endora <marcin.krupa@endora.pl>
 */
class ExpertSender_CDP_Activator {
	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'expertsender_cdp_mappings';
		
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			resource_type varchar(20) NOT NULL,
			wp_field varchar(50) NOT NULL,
			ecdp_field varchar(50) NOT NULL,
			PRIMARY KEY (id),
            UNIQUE INDEX es_mappings_resource_type_wp_field_unique (resource_type, wp_field)
		);";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		$table_name = $wpdb->prefix . 'expertsender_cdp_consents';
		
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			api_consent_id mediumint(9) NOT NULL,
			consent_location varchar(50) NOT NULL,
			consent_text TEXT,
			PRIMARY KEY (id),
            UNIQUE INDEX es_consents_api_consent_id_consent_location_unique (api_consent_id, consent_location)
		);";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		$table_name = $wpdb->prefix . 'expertsender_cdp_requests';
		
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            is_sent tinyint(1) DEFAULT 0 NOT NULL,
			url_address varchar(255) NOT NULL,
			json_body TEXT NOT NULL,
			resource_type varchar(20) NOT NULL,
			resource_id int NOT NULL,
			synchronization_id int,
			response TEXT,
			PRIMARY KEY (id),
            UNIQUE INDEX es_requests_resource_type_resource_id_unique (resource_type, resource_id)
		);";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		$table_name = $wpdb->prefix . 'expertsender_cdp_order_status_mappings';
		
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			wp_order_status varchar(50) NOT NULL,
			ecdp_order_status varchar(50) NOT NULL,
			PRIMARY KEY (id),
            UNIQUE (wp_order_status)
		);";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		if ( ! wp_next_scheduled( 'expertsender_cdp_cron_job' ) ) {
			wp_schedule_event( time(), 'every_minute', 'expertsender_cdp_cron_job' );
		}
	}
}
