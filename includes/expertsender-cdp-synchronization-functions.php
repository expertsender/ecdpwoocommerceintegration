<?php

/**
 * @return int
 */
function scortea_get_next_synchronization_id() {
    /** @var \wpdb */
    global $wpdb;

    $query = <<<SQL
        SELECT MAX(synchronization_id) AS last_sync
        FROM {$wpdb->prefix}expertsender_cdp_requests;
    SQL;

    $result = (int) $wpdb->get_var( $query );

    if ( $result > 0 ) {
        return $result + 1;
    }

    return 1;
}

/**
 * @param int $page
 * @param int $page_size
 *
 * @return array
 */
function scortea_get_synchronization_requests( $page = 1, $page_size = 10 ) {
    /** @var \wpdb */
    global $wpdb;

    $table = "{$wpdb->prefix}expertsender_cdp_requests";

    $total_pages_query = <<<SQL
        SELECT COUNT(id)
        FROM $table
    SQL;

    $total_pages = ceil( (int) $wpdb->get_var( $total_pages_query ) / $page_size );

    $offset = ( $page - 1 ) * $page_size;

    $items_query = <<<SQL
        SELECT *
        FROM $table
        WHERE is_sent = 0
        ORDER BY id
        LIMIT {$offset}, $page_size;
    SQL;

    $items = $wpdb->get_results( $items_query );

    return [
        'items' => $items,
        'total_pages' => $total_pages
    ];
}

/**
 * @param array $data
 * @param array $where
 *
 * @return void
 */
function scortea_update_synchronization( $data, $where ) {
    /** @var \wpdb */
    global $wpdb;

    $table = "{$wpdb->prefix}expertsender_cdp_requests";

    $wpdb->update(
        $table,
        $data,
        $where
    );
}
