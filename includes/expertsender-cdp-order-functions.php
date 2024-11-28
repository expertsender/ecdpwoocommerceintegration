<?php

defined ( 'ABSPATH' ) || exit;

/**
 * @param string $start_date
 * @param string $end_date
 *
 * @return array
 */
function es_get_orders_by_dates( $start_date, $end_date ) {
    /** @var \wpdb $wpdb */
    global $wpdb;

    $filters = array();

    $query = <<<SQL
        SELECT * 
        FROM {$wpdb->prefix}wc_orders 
    SQL;

    if ( ! empty( $start_date ) ) {
        $filters[] = "date_updated_gmt >= '{$start_date}'";
    }

    if ( ! empty( $end_date ) ) {
        $filters[] = "date_updated_gmt <= '{$end_date}'";
    }

    if ( ! empty( $filters ) ) {
        $where_query = implode( ' AND ', $filters );
        $query .= " WHERE " . $where_query;
    }

    $query .= ' ORDER BY id DESC';

    return $wpdb->get_results( $query ) ?? array();
}