<?php

/**
 * Fired during plugin activation
 *
 * @link       https://test.pl
 * @since      1.0.0
 *
 * @package    Expert_Sender
 * @subpackage Expert_Sender/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Expert_Sender
 * @subpackage Expert_Sender/includes
 * @author     Endora <marcin.krupa@endora.pl>
 */
class Expert_Sender_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'expert_sender_mappings';
		
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			resource_type varchar(20) NOT NULL,
			wp_field varchar(50) NOT NULL,
			ecdp_field varchar(50) NOT NULL,
			PRIMARY KEY  (id)
		);";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		$table_name = $wpdb->prefix . 'expert_sender_consents';
		
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			api_consent_id mediumint(9) NOT NULL,
			consent_location varchar(50) NOT NULL,
			consent_text TEXT,
			PRIMARY KEY  (id)
		);";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		$table_name = $wpdb->prefix . 'expert_sender_requests';
		
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

		$table_name = $wpdb->prefix . 'expert_sender_order_status_mappings';
		
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			wp_order_status varchar(50) NOT NULL,
			ecdp_order_status varchar(50) NOT NULL,
			PRIMARY KEY  (id)
		);";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		if ( ! wp_next_scheduled( 'expert_sender_cron_job' ) ) {
			wp_schedule_event( time(), 'every_minute', 'expert_sender_cron_job' );
		}
	}
}
