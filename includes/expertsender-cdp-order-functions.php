<?php

defined ( 'ABSPATH' ) || exit;

/**
 * @param string $start_date
 * @param string $end_date
 *
 * @return stdClass
 */
function es_get_orders_by_dates( $start_date, $end_date, $page = 1, $page_size = 10 ) {
    $statuses = wc_get_order_statuses();

    if ( isset( $statuses['wc-checkout-draft'] ) ) {
        unset( $statuses['wc-checkout-draft'] );
    }

    $args = array(
        'paginate' => true,
        'limit' => $page_size,
        'paged' => $page,
        'status' => array_keys( $statuses )
    );

    if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
        $args['date_modified'] = "{$start_date}...{$end_date}";
    } else if ( ! empty( $start_date ) ) {
        $args['date_modified'] = ">={$start_date}";
    } else if ( ! empty( $end_date ) ) {
        $args['date_modified'] = "<={$end_date}";
    }

    return wc_get_orders( $args );
}