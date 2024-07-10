<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    ExpertSender_CDP
 * @subpackage ExpertSender_CDP/includes
 * @author     Endora <marcin.krupa@endora.pl>
 */
class ExpertSender_CDP_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'expertsender_cdp_mappings';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

		$table_name = $wpdb->prefix . 'expertsender_cdp_consents';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

		$table_name = $wpdb->prefix . 'expertsender_cdp_requests';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

		$table_name = $wpdb->prefix . 'expertsender_cdp_order_status_mappings';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

		wp_clear_scheduled_hook( 'expertsender_cdp_cron_job' );
	}
}
