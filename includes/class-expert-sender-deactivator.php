<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin deactivation
 *
 * @link       https://test.pl
 * @since      1.0.0
 *
 * @package    Expert_Sender
 * @subpackage Expert_Sender/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Expert_Sender
 * @subpackage Expert_Sender/includes
 * @author     Endora <marcin.krupa@endora.pl>
 */
class Expert_Sender_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'expert_sender_mappings';

        // Drop the table if it exists
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

		$table_name = $wpdb->prefix . 'expert_sender_consents';

        // Drop the table if it exists
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

		$table_name = $wpdb->prefix . 'expert_sender_requests';

        // Drop the table if it exists
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

		$table_name = $wpdb->prefix . 'expert_sender_order_status_mappings';

        // Drop the table if it exists
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

		wp_clear_scheduled_hook( 'expert_sender_cron_job' );
	}

}
