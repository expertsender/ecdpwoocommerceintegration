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
        $errors[] = sprintf(
            __( 'Zduplikowane pola WooCommerce: %1$s', 'expertsender-cdp' ),
            implode( ',', $duplicated_wc_fields )
        );
    }

    if ( ! empty( $duplicated_ecdp_fields ) ) {
        $errors[] = sprintf(
            __( 'Zduplikowane pola ECDP: %1$s', 'expertsender-cdp' ),
            implode( ',', $duplicated_ecdp_fields )
        );
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

/**
 * @param \WC_Data $object
 * @param string $resource_type
 *
 * @return array
 */
function es_get_mapped_attributes( $object, $resource_type ) {
    $attributes = array();
    $variant_attributes = array();
    $mappings = es_get_field_mappings_by_resource_type( $resource_type );
    $data = $object->get_data();

    if ( $object instanceof \WC_Order_Item_Product ) {
        /** @var \WC_Meta_Data */
        foreach ( $object->get_meta_data() as $meta_data ) {
            $key = $meta_data->__get('key');
            $value = $meta_data->__get('value');

            if ( $key ) {
                $variant_attributes[ $key ] = $value;
            }
        }

        $product = wc_get_product( $object->get_product_id() );

        if ( $product ) {
            $data = $product->get_data();
        }
    }

    $flat_data = es_flatten_data_with_mappings( $data );
    $flat_data = array_merge( $flat_data, $variant_attributes );

    foreach ( $mappings as $mapping ) {
        if ( isset( $flat_data[ $mapping['wp_field'] ] ) ) {
            $attributes[] = array(
                'name' => $mapping['ecdp_field'],
                'value' => strval( $flat_data[ $mapping['wp_field'] ] )
            );
        }
    }

    return $attributes;
}

/**
 * @param array $data
 * @param string $prefix
 *
 * @return array
 */
function es_flatten_data_with_mappings( $data, $prefix = '' ) {
    $result = array();

    foreach ( $data as $key => $value ) {
        if ( is_array( $value ) ) {
            $result = array_merge( $result, es_flatten_data_with_mappings( $value, $prefix . $key . '.' ) );
        } else if ( ! is_object( $value ) ) {
            $result[ $prefix . $key ] = $value;
        }
    }

    return $result;
}

/**
 * @return array
 */
function es_get_customer_mapping_field_keys() {
    $customer = new WC_Customer();

    return array_keys( es_flatten_data_with_mappings( $customer->get_data() ) );
}

/**
 * @return array
 */
function es_get_order_mapping_field_keys() {
    $order = new WC_Order();

    return array_keys( es_flatten_data_with_mappings( $order->get_data() ) );
}

/**
 * @return array
 */
function es_get_product_mapping_field_keys() {
    $product = new WC_Product();
    $attributes = array_keys( es_flatten_data_with_mappings( $product->get_data() ) );
    $product_attribute_taxonomies = wc_get_attribute_taxonomies();
    $variant_attributes = array();

    if ( $product_attribute_taxonomies ) {
        foreach ( $product_attribute_taxonomies as $taxonomy ) {
            $variant_attributes[] = 'pa_' . $taxonomy->attribute_name;
        }
    }

    return array_merge( $attributes, $variant_attributes );
}
