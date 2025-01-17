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
        WHERE FIND_IN_SET('wc-{$status}' , wp_order_statuses) OR FIND_IN_SET('{$status}' , wp_order_statuses)
            OR FIND_IN_SET('wc-{$status}' , wp_custom_order_statuses) OR FIND_IN_SET('{$status}' , wp_custom_order_statuses)
        LIMIT 1
    SQL;

    $result = $wpdb->get_results( $query, ARRAY_A );

    return count( $result ) ? $result[ 0 ][ 'ecdp_order_status' ] : 'Paid';
}

/**
 * @param array $mappings
 *
 * @return int|bool
 */
function es_insert_order_status_mappings( $mappings ) {
    /** @var \wpdb */
    global $wpdb;

    $result = false;
    $placeholders = array();
    $values = array();

    $query = <<<SQL
        INSERT INTO {$wpdb->prefix}expertsender_cdp_order_status_mappings (wp_order_statuses, wp_custom_order_statuses, ecdp_order_status)
        VALUES
    SQL;

    foreach ( $mappings as $mapping ) {
        $placeholders[] = "('%s', '%s', '%s')";
        $wp_order_statuses = is_array( $mapping['wp_order_statuses'] ) ?
            implode( ',', $mapping[ 'wp_order_statuses' ] ) :
            $mapping['wp_order_statuses'];

        array_push(
            $values,
            $wp_order_statuses,
            $mapping[ 'wp_custom_order_statuses' ] ?? '',
            $mapping[ 'ecdp_order_status' ] 
        );
    }

    if ( ! empty ( $values ) ) {
        $query .= ' ' . implode( ', ', $placeholders );
        $result = $wpdb->query(
            $wpdb->prepare( $query, $values )
        );
    }

    return $result;
}

/**
 * @return int|bool
 */
function es_truncate_order_status_mappings() {
    /** @var \wpdb $wpdb */
    global $wpdb;

    $query = <<<SQL
        DELETE FROM {$wpdb->prefix}expertsender_cdp_order_status_mappings;
    SQL;

    return $wpdb->query( $query );
}

/**
 * @return array|null
 */
function es_get_all_order_status_mappings() {
    /** @var \wpdb $wpdb */
    global $wpdb;

    $query = <<<SQL
        SELECT *
        FROM {$wpdb->prefix}expertsender_cdp_order_status_mappings
    SQL;

    return $wpdb->get_results( $query, ARRAY_A );
}

/**
 * @return int
 */
function es_get_max_order_status_mapping_id() {
    /** @var \wpdb $wpdb */
    global $wpdb;

    $query = <<<SQL
        SELECT MAX(id)
        FROM {$wpdb->prefix}expertsender_cdp_order_status_mappings;
    SQL;

    return (int) $wpdb->get_var( $query );
}

/**
 * @param object $mapping
 *
 * @return array
 */
function es_get_order_status_mapping_wc_statuses( $mapping ) {
    if ( ! empty( $mapping['wp_order_statuses'] ) ) {
        return explode( ',', $mapping['wp_order_statuses'] );
    }

    return array();
}

/**
 * @param array $mappings
 *
 * @return array
 */
function es_validate_order_status_mapping_data( $mappings ) {
    $errors = array();
    $all_duplicated_wc_statuses = array();
    $all_duplicated_ecdp_statuses = array();

    foreach ( $mappings as &$mapping ) {
        if ( empty( $mapping['wp_order_statuses'] ) && empty( $mapping['wp_custom_order_statuses'] ) ) {
            return array( __('Każde mapowanie musi mieć przynajmniej jeden status WooCommerce i status ECDP.', 'expertsender-cdp' ) );
        }

        if ( isset( $mapping['wp_order_statuses'] ) && is_string( $mapping['wp_order_statuses'] ) ) {
            $mapping['wp_order_statuses'] = explode( ',', $mapping['wp_order_statuses'] );
        }
    }

    foreach ( $mappings as $index => $mapping ) {
        foreach ( $mappings as $compared_index => $compared_mapping ) {
            if ( $index >= $compared_index || $mapping[ 'id' ] === $compared_mapping[ 'id' ] ) {
                continue;
            }

            $mapping_custom_order_statuses = ! empty( $mapping['wp_custom_order_statuses'] ) ?
                explode( ',', $mapping['wp_custom_order_statuses'] ) :
                array();
            $mapping_wc_statuses = array_merge(
                $mapping['wp_order_statuses'] ?? array(),
                $mapping_custom_order_statuses
            );

            $compared_mapping_custom_order_statuses = ! empty( $compared_mapping['wp_custom_order_statuses'] ) ?
                explode( ',', $compared_mapping['wp_custom_order_statuses'] ) :
                array();
            $compared_mapping_wc_statuses = array_merge(
                $compared_mapping['wp_order_statuses'] ?? array(),
                $compared_mapping_custom_order_statuses
            );

            $duplicated_wc_order_statuses = array_intersect(
                $mapping_wc_statuses,
                $compared_mapping_wc_statuses
            );

            if ( ! empty ( $duplicated_wc_order_statuses ) ) {
                $all_duplicated_wc_statuses = array_merge(
                    $all_duplicated_wc_statuses,
                    $duplicated_wc_order_statuses
                );
            }

            if ( $mapping['ecdp_order_status'] === $compared_mapping['ecdp_order_status'] ) {
                $all_duplicated_ecdp_statuses[] = $mapping['ecdp_order_status'];
            }
        }
    }

    if ( ! empty( $all_duplicated_wc_statuses ) ) {
        $all_duplicated_wc_statuses = array_unique( $all_duplicated_wc_statuses );
        $errors[] = sprintf(
            __( 'Zduplikowane statusy WooCommerce: %1$s', 'expertsender-cdp' ),
            implode( ',', $all_duplicated_wc_statuses )
        );
    }

    if ( ! empty( $all_duplicated_ecdp_statuses ) ) {
        $all_duplicated_ecdp_statuses = array_unique( $all_duplicated_ecdp_statuses );
        $errors[] = sprintf(
            __( 'Zduplikowane statusy ECDP: %1$s', 'expertsender-cdp' ),
            implode( ',', $all_duplicated_ecdp_statuses )
        );
    }

    return $errors;
}

/**
 * @return void
 */
function es_insert_default_order_status_mappings() {
    $mappings = array(
        array(
            'wp_order_statuses' => 'placed,pending,on-hold',
            'ecdp_order_status' => 'Placed'
        ),
        array(
            'wp_order_statuses' => 'processing',
            'ecdp_order_status' => 'Paid'
        ),
        array(
            'wp_order_statuses' => 'completed',
            'ecdp_order_status' => 'Completed'
        ),
        array(
            'wp_order_statuses' => 'cancelled,refunded,failed',
            'ecdp_order_status' => 'Cancelled'
        )
    );

    es_insert_order_status_mappings( $mappings );
}
