<?php

defined ( 'ABSPATH' ) || exit;

/**
 * @param array $mappings
 *
 * @return int|bool
 */
function es_insert_field_mappings( $mappings ) {
    /** @var \wpdb */
    global $wpdb;

    $result = false;
    $placeholders = array();
    $values = array();

    $query = <<<SQL
        INSERT INTO {$wpdb->prefix}expertsender_cdp_mappings (resource_type, wp_field, ecdp_field)
        VALUES
    SQL;

    foreach ( $mappings as $mapping ) {
        $placeholders[] = "('%s', '%s', '%s')";
        array_push(
            $values,
            $mapping['resource_type'],
            $mapping['wp_field'],
            $mapping['ecdp_field']
        );
    }

    if ( ! empty( $values ) ) {
        $query .= ' ' . implode( ',', $placeholders );
        $result = $wpdb->query(
            $wpdb->prepare( $query, $values )
        );
    }

    return $result;
}

/**
 * @return int
 */
function es_get_max_field_mapping_id() {
    /** @var \wpdb $wpdb */
    global $wpdb;

    $query = <<<SQL
        SELECT MAX(id)
        FROM {$wpdb->prefix}expertsender_cdp_mappings;
    SQL;

    return (int) $wpdb->get_var( $query );
}

/**
 * @param array $mappings
 *
 * @return array
 */
function es_validate_field_mapping_data( $mappings ) {
    $errors = array();
    $duplicated_wc_fields = array();
    $duplicated_ecdp_fields = array();

    foreach ( $mappings as $index => $mapping ) {
        if ( empty( $mapping['wp_field'] ) || empty( $mapping['ecdp_field'] ) ) {
            return array( __( 'Każde mapowanie musi mieć wybrane pole WooCommerce i ECDP.', 'expertsender-cdp' ) );
        }

        foreach ( $mappings as $compared_index => $compared_mapping ) {
            if ( $index >= $compared_index || $mapping[ 'id' ] === $compared_mapping[ 'id' ] ) {
                continue;
            }

            if ( $mapping['resource_type'] !== $compared_mapping['resource_type'] ) {
                continue;
            }

            if ( $mapping['wp_field'] === $compared_mapping['wp_field'] ) {
                $duplicated_wc_fields[] = $mapping['wp_field'];
            }

            if ( $mapping['ecdp_field'] === $compared_mapping['ecdp_field'] ) {
                $duplicated_ecdp_fields[] = $mapping['ecdp_field'];
            }
        }
    }

    if ( ! empty( $duplicated_wc_fields ) ) {
        $errors[] = __( 'Zduplikowane pola WooCommerce:', 'expertsender-cdp' )
            . ' ' . implode( ',', $duplicated_wc_fields );
    }

    if ( ! empty( $duplicated_ecdp_fields ) ) {
        $errors[] = __( 'Zduplikowane pola ECDP:', 'expertsender-cdp' )
            . ' ' . implode( ',', $duplicated_ecdp_fields );
    }

    return $errors;
}

/**
 * @return array|null
 */
function es_get_all_field_mappings() {
    /** @var \wpdb $wpdb */
    global $wpdb;

    $query = <<<SQL
        SELECT *
        FROM {$wpdb->prefix}expertsender_cdp_mappings
    SQL;

    return $wpdb->get_results( $query, ARRAY_A );
}

function es_get_field_mappings_by_resource_type( $resource_type ) {
    /** @var \wpdb $wpdb */
    global $wpdb;

    $query = <<<SQL
        SELECT * FROM {$wpdb->prefix}expertsender_cdp_mappings
        WHERE resource_type = '{$resource_type}';
    SQL;

    return $wpdb->get_results( $query, ARRAY_A );
}

/**
 * @return int|bool
 */
function es_truncate_field_mappings() {
    /** @var \wpdb $wpdb */
    global $wpdb;

    $query = <<<SQL
        DELETE FROM {$wpdb->prefix}expertsender_cdp_mappings;
    SQL;

    return $wpdb->query( $query );
}