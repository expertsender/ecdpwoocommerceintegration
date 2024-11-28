<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin uninstall.
 *
 * This class defines all code necessary to run during the plugin's uninstall.
 *
 * @since      1.0.0
 * @package    ExpertSender_CDP
 * @subpackage ExpertSender_CDP/includes
 * @author     Endora <marcin.krupa@endora.pl>
 */
class ExpertSender_CDP_Uninstaller {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function uninstall() {
        /** @var \wpdb */
        global $wpdb;

        $table_name = $wpdb->prefix . 'expertsender_cdp_mappings';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

		$table_name = $wpdb->prefix . 'expertsender_cdp_consents';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

		$table_name = $wpdb->prefix . 'expertsender_cdp_requests';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");

		$table_name = $wpdb->prefix . 'expertsender_cdp_order_status_mappings';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
	}
}
