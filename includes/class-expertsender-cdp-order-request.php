<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ExpertSender_CDP_Order_Request
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    const RESOURCE_PRODUCT = 'product';
    const RESOURCE_CUSTOMER = 'customer';
    const RESOURCE_ORDER = 'order';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct()
    {
        add_action(
            'woocommerce_checkout_order_processed',
            array( $this, 'expertsender_cdp_order_save_request' )
        );

        add_action(
            'woocommerce_update_order',
            array( $this, 'expertsender_cdp_order_save_request' )
        );
    }

    /**
     * @param int $order_id
     * @param WC_Order $order
     * @param int|null $sync_id
     * 
     * @return void
     */
    public function expertsender_cdp_order_save_request(
        $order_id,
        $order = null,
        $sync_id = null
    ) {
        /** @var \wpdb */
        global $wpdb;

        if ( null === $order ) {
            $order = wc_get_order( $order_id );
        }

        $customer = new WC_Customer( $order->get_user_id() );
        $order_data[ 'id' ] = (string) $order->get_id();
        $order_data[ 'date' ] = $order->get_data()[ 'date_modified' ]->date( 'Y-m-d\TH:i:s.u\Z' );
        $order_data[ 'timeZone' ] = 'UTC';
        $website_id = get_option( 'expertsender_cdp_website_id', null );

        if ( empty( $website_id ) ) {
            $website_id = null;
        }

        $order_data[ 'websiteId' ] = $website_id;
        $order_data[ 'status' ] = es_get_mapped_order_status( $order->get_status() );
        $order_data[ 'currency' ] = $order->get_currency();
        $order_data[ 'totalValue' ] = (float) $order->get_total();
        $order_data[ 'returnsValue' ] = $order->get_total_refunded();
        $customer_data[ 'email' ] = $customer->get_email();

        if ( get_option('expertsender_cdp_enable_script') ) {
            $customer_data[ 'phone' ] = $customer->get_billing_phone();
        }

        $customer_data[ 'crmId' ] = $customer->get_billing_phone();
        $order_data[ 'customer' ] = $customer_data;
        $consents_data = ExpertSender_CDP_Client_Request::get_consents_from_request(
            ExpertSender_CDP_Admin::FORM_CHECKOUT_KEY
        );
        $order_data[ 'customer' ][ 'consentsData' ] = ! empty ( $consents_data ) ? $consents_data : null;
        $items = $order->get_items();
        $products = [];

        foreach ( $items as $item ) {
            $product_id = $item->get_product_id();
            $product_item = wc_get_product( $product_id );
            $product[ 'id' ] = (string) $product_item->get_id();
            $product[ 'name' ] = $product_item->get_name();
            $product[ 'price' ] = $product_item->get_price();
            $product[ 'quantity' ] = $item->get_quantity();
            $returned = 0;

            foreach ( $order->get_refunds() as $refund ) {
                foreach ( $refund->get_items() as $refund_item ) {
                    if ( $refund_item->get_product_id() === $product_id ) {
                        $returned += $refund_item->get_quantity();
                        break;
                    }
                }
            }

            $product[ 'returned' ] = abs( $returned );
            $product[ 'url' ] = $product_item->get_permalink();
            $image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'single-post-thumbnail' );

            if ( is_array( $image_url ) && ! empty( $image_url ) ) {
                $image_url = $image_url[ 0 ];
            }

            if ( false === $image_url ) {
                $image_url = wc_placeholder_img_src();
            }
        
            $product[ 'imageUrl' ] = $image_url;

            $category_ids = $product_item->get_category_ids();
            $categories_array = array();
            if ( ! empty( $category_ids ) ) {
                foreach ( $category_ids as $category_id ) {
                    $this->log_order_details( $category_id );
                    $this->log_order_details(
                        get_term( $category_id, 'product_cat' )->name
                    );
                    $categories_array[] = get_term(
                        $category_id,
                        'product_cat'
                    )->name;
                }
            }

            $product[ 'category' ] = implode( ', ', $categories_array );
            $product_attributes = [];
            $attributes = $product_item->get_attributes();
            $table_name = $wpdb->prefix . 'expertsender_cdp_mappings';

            if ( ! empty( $attributes ) ) {
                $this->log_order_details($attributes);
                foreach ( $attributes as $attribute ) {
                    $query = $wpdb->prepare(
                        "SELECT * FROM $table_name WHERE resource_type = %s AND wp_field = %s LIMIT 1",
                        $this::RESOURCE_PRODUCT,
                        str_replace( 'pa_', '', $attribute->get_name() )
                    );

                    $result = $wpdb->get_row( $query, OBJECT );

                    if ( $result ) {
                        $product_attributes[] = [
                            'name' => $result->ecdp_field,
                            'value' => $product_item->get_attribute(
                                $attribute->get_name()
                            ),
                        ];
                    }
                }

                $product[ 'productAttributes' ] = $product_attributes;
            }

            $products[] = $product;
        }

        $order_data[ 'products' ] = $products;

        $order_attributes = array();

        $order_mappings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE resource_type = %s",
                $this::RESOURCE_ORDER
            )
        );

        $orderFlattenData = $this->flatten( $order->get_data() );

        foreach ( $order_mappings as $order_mapping ) {
            $mapping[ 'name' ] = $order_mapping->ecdp_field;
            $mapping[ 'value' ] = strval(
                $orderFlattenData[ $order_mapping->wp_field ]
            );
            $order_attributes[] = $mapping;
        }

        $order_data[ 'orderAttributes' ] = $order_attributes;

        $body = json_encode( [
            'mode' => 'AddOrReplace',
            'matchBy' => 'Email',
            'data' => [ $order_data ],
        ] );

        $url = ES_API_URL . 'orders';

        $table_name = $wpdb->prefix . 'expertsender_cdp_requests';

        $wpdb->replace( $table_name, array(
            'created_at' => current_time( 'mysql' ),
            'is_sent' => false,
            'url_address' => $url,
            'json_body' => $body,
            'resource_type' => 'order',
            'resource_id' => $order_id,
            'synchronization_id' => $sync_id
        ));
    }

    public function flatten($array, $prefix = '')
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result =
                    $result + $this->flatten($value, $prefix . $key . '.');
            } else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }

    private function log_order_details($order)
    {
        $fullLog = json_encode($order);
        $log_message = $fullLog;
        $log_message .= "\n";

        $logger = expertsender_cdp_get_logger();
        $logger->debug( $log_message, array( 'source' => 'custom_order' ) );
    }
}
