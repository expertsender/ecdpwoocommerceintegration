<?php

defined ( 'ABSPATH' ) || exit;

/**
 * @param array $consents
 *
 * @return int|bool
 */
function es_insert_consents( $consents ) {
    /** @var \wpdb */
    global $wpdb;

    $result = false;
    $placeholders = array();
    $values = array();

    $query = <<<SQL
        INSERT INTO {$wpdb->prefix}expertsender_cdp_consents (api_consent_id, consent_location, consent_text)
        VALUES
    SQL;

    foreach ( $consents as $consent ) {
        $placeholders[] = "('%s', '%s', '%s')";
        array_push(
            $values,
            $consent['api_consent_id'],
            $consent['consent_location'],
            $consent['consent_text']
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
 * @return array|null
 */
function es_get_all_consents() {
    /** @var \wpdb $wpdb */
    global $wpdb;

    $query = <<<SQL
        SELECT *
        FROM {$wpdb->prefix}expertsender_cdp_consents;
    SQL;

    return $wpdb->get_results( $query, ARRAY_A );
}

/**
 * @return int|bool
 */
function es_truncate_consents() {
    /** @var \wpdb $wpdb */
    global $wpdb;

    $query = <<<SQL
        DELETE FROM {$wpdb->prefix}expertsender_cdp_consents;
    SQL;

    return $wpdb->query( $query );
}

/**
 * @return int
 */
function es_get_max_consent_id() {
    /** @var \wpdb $wpdb */
    global $wpdb;

    $query = <<<SQL
        SELECT MAX(id)
        FROM {$wpdb->prefix}expertsender_cdp_consents;
    SQL;

    return (int) $wpdb->get_var( $query );
}

function es_validate_consent_data( $consents ) {
    $errors = array();

    foreach ( $consents as $index => $consent ) {
        if (
            empty( $consent['api_consent_id'] ) ||
            empty( $consent['consent_location'] ) ||
            empty( $consent['consent_text'] )
        ) {
            return array( __( 'Wszystkie pola mapowania muszą być uzupełnione.', 'expertsender-cdp' ) );
        }

        foreach ( $consents as $compared_index => $compared_consent ) {
            if ( $index >= $compared_index || $consent[ 'id' ] === $compared_consent[ 'id' ] ) {
                continue;
            }

            if (
                $consent['api_consent_id'] === $compared_consent['api_consent_id'] &&
                $consent['consent_location'] === $compared_consent['consent_location']
            ) {
                return array(
                    __( 'Każda zgoda ECDP może zostać zmapowana tylko raz do danej lokalizacji', 'expertsender-cdp')
                );
            }
        }
    }

    return $errors;
}
