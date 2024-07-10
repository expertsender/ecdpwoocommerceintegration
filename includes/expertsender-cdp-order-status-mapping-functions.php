<?php

defined ( 'ABSPATH' ) || exit;

/**
 * @param string $status
 *
 * @return string|null
 */
function es_get_mapped_order_status( $status ) {
    global $wpdb;

    if ( null === $status ) {
        return $status;
    }

    $table = $wpdb->prefix . 'expertsender_cdp_order_status_mappings';
    $query = <<<SQL
        SELECT ecdp_order_status
        FROM {$table}
        WHERE wp_order_status = %s OR wp_order_status = %s
        LIMIT 1
    SQL;

    $result = $wpdb->get_results(
        $wpdb->prepare( $query, 'wc-' . $status, $status ),
        ARRAY_A
    );

    return count( $result ) ? $result[ 0 ][ 'ecdp_order_status' ] : null;
}
