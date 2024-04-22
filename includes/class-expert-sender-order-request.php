<?php

class Expert_Sender_Order_Request
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
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('woocommerce_checkout_order_processed', [
            $this,
            'expert_sender_place_order',
        ]);
        add_action('woocommerce_order_status_pending', [
            $this,
            'expert_sender_order_pending',
        ]);
        add_action('woocommerce_order_status_processing', [
            $this,
            'expert_sender_order_processing',
        ]);
        add_action('woocommerce_order_status_on-hold', [
            $this,
            'expert_sender_order_on_hold',
        ]);
        add_action('woocommerce_order_status_completed', [
            $this,
            'expert_sender_order_completed',
        ]);
        add_action('woocommerce_order_status_cancelled', [
            $this,
            'expert_sender_order_canceled',
        ]);
        add_action('woocommerce_order_status_refunded', [
            $this,
            'expert_sender_order_refuned',
        ]);
        add_action('woocommerce_order_status_failed', [
            $this,
            'expert_sender_order_failed',
        ]);

        add_action('woocommerce_payment_complete', [
            $this,
            'expert_sender_order_paid',
        ]);
    }

    public function expert_sender_place_order($orderId)
    {
        $status = 'Placed';
		$this->expert_sender_order_save_request($orderId, $status);
    }

    public function expert_sender_order_paid($orderId)
    {
        $status = 'Paid';
        $slug = $this->expert_sender_get_api_order_status_slug($status);
        if ($slug) {
            $this->expert_sender_order_save_request($orderId, $slug);
        }
    }

    public function expert_sender_order_pending($orderId)
    {
        $status = 'Pending';
        $slug = $this->expert_sender_get_api_order_status_slug($status);
        if ($slug) {
            $this->expert_sender_order_save_request($orderId, $slug);
        }
    }

    public function expert_sender_order_processing($orderId)
    {
        $status = 'Processing';
        $slug = $this->expert_sender_get_api_order_status_slug($status);
        if ($slug) {
            $this->expert_sender_order_save_request($orderId, $slug);
        }
    }

    public function expert_sender_order_on_hold($orderId)
    {
        $status = 'On-hold';
        $slug = $this->expert_sender_get_api_order_status_slug($status);
        if ($slug) {
            $this->expert_sender_order_save_request($orderId, $slug);
        }
    }

    public function expert_sender_order_completed($orderId)
    {
        $status = 'Completed';
        $slug = $this->expert_sender_get_api_order_status_slug($status);
        if ($slug) {
            $this->expert_sender_order_save_request($orderId, $slug);
        }
    }

    public function expert_sender_order_canceled($orderId)
    {
        $status = 'Canceled';
        $slug = $this->expert_sender_get_api_order_status_slug($status);
        if ($slug) {
            $this->expert_sender_order_save_request($orderId, $slug);
        }
    }

    public function expert_sender_order_refunded($orderId)
    {
        $status = 'Refunded';
        $slug = $this->expert_sender_get_api_order_status_slug($status);
        if ($slug) {
            $this->expert_sender_order_save_request($orderId, $slug);
        }
    }

    public function expert_sender_order_failed($orderId)
    {
        $status = 'Failed';
        $slug = $this->expert_sender_get_api_order_status_slug($status);
        if ($slug) {
            $this->expert_sender_order_save_request($orderId, $slug);
        }
    }

    public function expert_sender_order_save_request($orderId, $status)
    {
        $order = wc_get_order($orderId);

        if ($order->get_customer_id());
        $customer = new WC_Customer($order->get_customer_id());

        $orderData['id'] = strval($order->get_id() + 1000);
        $orderData['date'] = $order
            ->get_data()
            ['date_modified']->date('Y-m-d\TH:i:s.u\Z');
        $orderData['timeZone'] = 'GMT';

        $orderData['websiteId'] = get_option('expert_sender_website_id');

        $orderData['status'] = $status;

        $orderData['currency'] = $order->get_currency();
        $orderData['totalValue'] = $order->get_total();
        $orderData['returnsValue'] = '0';

        $customerData['email'] = $customer->get_email();
        if (get_option('expert_sender_enable_script')) {
            $customerData['phone'] = $customer->get_billing_phone();
        }
        $customerData['crmId'] = $customer->get_billing_phone();
        $orderData['customer'] = $customerData;

        $items = $order->get_items();
        $products = [];
        foreach ($items as $item_id => $item) {
            $product_id = $item->get_product_id();
            // Get the product object
            $productItem = wc_get_product($product_id);

            $product['id'] = strval($productItem->get_id());
            $product['name'] = $productItem->get_name();
            $product['price'] = strval($productItem->get_price());
            $product['quantity'] = strval($item->get_quantity());
            $product['returned'] = strval(0); //TODO: Co to?
            $product['url'] = $productItem->get_permalink();
            $product['imageUrl'] = $productItem->get_image();

            $category_ids = $productItem->get_category_ids();
            $categoriesArray = [];
            if (!empty($category_ids)) {
                foreach ($category_ids as $category_id) {
					$this->log_order_details($category_id);
					$this->log_order_details(get_term( $category_id, 'product_cat' )->name);
                    $categoriesArray[] = get_term( $category_id, 'product_cat' )->name;
                }
            }

            $product['category'] = implode(', ', $categoriesArray);

            $productAttributes = [];

            $attributes = $productItem->get_attributes();

			global $wpdb;
			$table_name = $wpdb->prefix . 'expert_sender_mappings';

            if (!empty($attributes)) {
                foreach ($attributes as $attribute) {

					$query = 
						$wpdb->prepare(
							"SELECT * FROM $table_name WHERE resource_type = %s AND wp_field = %s LIMIT 1",
							$this::RESOURCE_PRODUCT, 
							str_replace('pa_', '', $attribute->get_name())
					);
					
					$result = $wpdb->get_row($query, OBJECT);

					if($result){
						$productAttributes[] = [
							'name' => $result->ecdp_field,
							'value' => $productItem->get_attribute(
								$attribute->get_name()
							),
						];
					}
                }

                $product['productAttributes'] = $productAttributes;
            }

            $products[] = $product;
        }

        $orderData['products'] = $products;

		$orderAttributes = [];
		
		$orderMappings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE resource_type = %s",
                $this::RESOURCE_ORDER
            )
        );

		$orderFlattenData = $this->flatten($order->get_data());
		
		foreach($orderMappings as $orderMapping) {
			$orderMapping->wp_field;
			$orderMapping->ecdp_field;
			$mapping['name'] = $orderMapping->ecdp_field;
			$mapping['value'] = strval($orderFlattenData[$orderMapping->wp_field]);
			$orderAttributes[] = $mapping;
		}

		$orderData['orderAttributes'] = $orderAttributes;

        $body = json_encode([
            'mode' => 'AddOrReplace',
            'matchBy' => 'Email',
            'data' => [$orderData],
        ]);

        $url = 'https://api.ecdp.app/orders';

        global $wpdb;
        $table_name = $wpdb->prefix . 'expert_sender_requests';

        $wpdb->insert($table_name, [
            'created_at' => current_time('mysql'),
            'is_sent' => false,
            'url_address' => $url,
            'json_body' => $body,
			'resource_type' => 'order',
			'resource_id' => $orderId
        ]);
    }

    public function expert_sender_get_api_order_status_slug($wpSlug)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'expert_sender_order_status_mappings';
        $apiStatus = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE wp_order_status = %s ORDER BY id ASC LIMIT 1",
                $wpSlug
            )
        );
        $apiStatus = $wpdb->get_row($apiStatus, OBJECT);
        if ($apiStatus->ecdp_order_status) {
            return $apiStatus->ecdp_order_status;
        }
        return null;
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

        // Log to file
        $log_file = WP_CONTENT_DIR . '/custom_order_logs.log';
        file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
    }
}
