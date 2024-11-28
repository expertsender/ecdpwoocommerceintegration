<?php

defined ( 'ABSPATH' ) || exit;

/**
* @param array $customer_data
* @param int|null $resource_id
*
* @return void
*/
function es_cdp_add_or_update_customer( $customer_data, $resource_id = null ) {
    /** @var \wpdb */
    global $wpdb;

    $url = ES_API_URL . 'customers';
    $body = json_encode( array(
        'mode' => 'AddOrUpdate',
        'matchBy' => 'Email',
        'data' => array( $customer_data ),
    ) );

    if ( null !== $resource_id ) {
        $request = es_get_request_by_resource( $resource_id, ExpertSender_CDP_Admin::RESOURCE_CUSTOMER );
    }

    $table_name = $wpdb->prefix . 'expertsender_cdp_requests';
    $request_data = array(
        'created_at' => current_time('mysql'),
        'url_address' => $url,
        'json_body' => $body,
        'resource_type' => ExpertSender_CDP_Admin::RESOURCE_CUSTOMER,
        'resource_id' => $customer_data['id'],
        'is_sent' => false
    );

    if ( isset( $request ) ) {
        $request_data['id'] = $request['id'];
    }

    $wpdb->replace( $table_name, $request_data );
}

/**
 * @param int $resource_id
 * @param string $resource_type
 *
 * @return array|null
 */
function es_get_request_by_resource( $resource_id, $resource_type ) {
    /** @var \wpdb */
    global $wpdb;

    $query = <<<SQL
        SELECT *
        FROM {$wpdb->prefix}expertsender_cdp_requests
        WHERE resource_id = $resource_id AND resource_type = '{$resource_type}';
    SQL;

    return $wpdb->get_row( $query, ARRAY_A );
}
