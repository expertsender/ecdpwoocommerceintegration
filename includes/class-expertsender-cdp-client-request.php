<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ExpertSender_CDP_Client_Request
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

    const RESOURCE_CUSTOMER = 'customer';

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
    }

    /**
     * @param string $consent_form
     * @return array
     */
    public static function get_consents_from_request( $consent_form ) {
        global $wpdb;

        $empty_request_forms = array(
            ExpertSender_CDP_Admin::FORM_CUSTOMER_SETTINGS_KEY, 
            ExpertSender_CDP_Admin::FORM_NEWSLETTER_KEY
        );

        if ( ! in_array( $consent_form, $empty_request_forms ) &&
            ! isset( $_POST[ExpertSender_CDP_Inject_Consent::CONSENT_INPUT_KEY] )
        ) {
            return array();
        }

        $request_consents = $_POST[ExpertSender_CDP_Inject_Consent::CONSENT_INPUT_KEY] ?? array();

        $consents_table_name = $wpdb->prefix . 'expertsender_cdp_consents';
        $query = "SELECT * FROM $consents_table_name WHERE consent_location = %s";

        if ( ! in_array( $consent_form, $empty_request_forms ) ) {
            $consent_ids = implode( ',', array_keys( $request_consents ) );
            $query .= "AND api_consent_id IN ({$consent_ids})";
        }

        $consents = $wpdb->get_results(
            $wpdb->prepare( $query, $consent_form)
        );

        $consents_data = array();

        foreach ( $consents as $consent ) {
            $value = $request_consents[$consent->api_consent_id] ?? '0';

            if ( $value === '1' || ExpertSender_CDP_Admin::FORM_NEWSLETTER_KEY === $consent_form ) {
                $value = 'True';
            } else if ( ExpertSender_CDP_Admin::FORM_CUSTOMER_SETTINGS_KEY !== $consent_form ) {
                continue;
            } else {
                $value = 'False';
            }

            $consents_data[] = array(
                'id' => $consent->api_consent_id,
                'value' => $value
            );
        }

        $confirmation_message_id = null;

        if ( ExpertSender_CDP_Admin::OPTION_VALUE_DOUBLE_OPT_IN === ExpertSender_CDP_Admin::get_opt_in_option_by_form( $consent_form ) ) {
            $confirmation_message_id = ExpertSender_CDP_Admin::get_confirmation_message_id_option_by_form( $consent_form );
        }

        return empty( $consents_data ) ? array() : array(
            'consents' => $consents_data,
            'force' => true,
            'confirmationMessageId' => $confirmation_message_id
        );
    }

    /**
     * @param array $customerData
     * @param bool $sync
     * @return void
     */
    public static function expertsender_cdp_add_or_update_customer( $customerData, $sync = false )
    {
        $url = ES_API_URL . 'customers';
        $logger = expertsender_cdp_get_logger();

        $body = json_encode([
            'mode' => 'AddOrUpdate',
            'matchBy' => 'Email',
            'data' => [$customerData],
        ]);

        global $wpdb;

        $table_name = $wpdb->prefix . 'expertsender_cdp_requests';
        if ( !$sync ) {
            $wpdb->replace($table_name, [
                'created_at' => current_time('mysql'),
                'is_sent' => false,
                'url_address' => $url,
                'json_body' => $body,
                'resource_type' => 'customer',
                'resource_id' => 1,
            ]);
        } else {
            $headers = [
                'Accept' => 'application/json',
                'x-api-key' => get_option( ExpertSender_CDP_Admin::OPTION_API_KEY ),
                'Content-Type' => 'application/json',
            ];

            $response = wp_remote_post($url, [
                'headers' => $headers,
                'body' => $body,
            ]);

            $logger->debug( 'Custom method executed', array( 'source' => 'cron' ) );
            $response = wp_remote_retrieve_body($response);
            $wpdb->replace($table_name, [
                'created_at' => current_time('mysql'),
                'is_sent' => true,
                'url_address' => $url,
                'json_body' => $body,
                'resource_type' => 'customer',
                'resource_id' => 1,
                'response' => $response
            ]);
        }
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in ExpertSender_CDP_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The ExpertSender_CDP_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/expertsender_cdp-public.css',
            [],
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in ExpertSender_CDP_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The ExpertSender_CDP_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/expertsender_cdp-public.js',
            ['jquery'],
            $this->version,
            false
        );
    }

    public function expertsender_cdp_create_customer(
        $customerId,
        $customerData,
        $password
    ) {
        $customerApiData = [];
        $customerApiData['email'] = $customerData['user_email'];
        $customerApiData['crmId'] = strval($customerId);
        $consents_data = self::get_consents_from_request( ExpertSender_CDP_Admin::FORM_REGISTRATION_KEY );
        $customerApiData['consentsData'] = ! empty ( $consents_data ) ? $consents_data : null;
        self::expertsender_cdp_add_or_update_customer($customerApiData, true);
    }

    public function expertsender_cdp_edit_customer($user_id)
    {
        $customer = new WC_Customer(get_current_user_id());

        global $wpdb;
        $table_name = $wpdb->prefix . 'expertsender_cdp_consents';

        $consents = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE consent_location = %s",
                'customer_settings'
            )
        );

        $consentValues = [];

        foreach ($consents as $consent) {
            $consentValues[$consent->api_consent_id] =
                isset($_POST['consent']) &&
                isset($_POST['consent'][$consent->api_consent_id])
                    ? 1
                    : 0;
        }

        $customer = new WC_Customer($user_id);

        $customerApiData['email'] = $customer->get_email();
        $customerApiData['crmId'] = strval($customer->get_id());
        $customerApiData['firstName'] = $customer->get_first_name();
        $customerApiData['lastName'] = $customer->get_last_name();
        $consents_data = self::get_consents_from_request( ExpertSender_CDP_Admin::FORM_CUSTOMER_SETTINGS_KEY );
        $customerApiData['consentsData'] = ! empty ( $consents_data ) ? $consents_data : null;

        $customAttributes = [];

        global $wpdb;
        $table_name = $wpdb->prefix . 'expertsender_cdp_mappings';
        $query = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE resource_type = %s",
                $this::RESOURCE_CUSTOMER
            )
        );
        $flattenData = $this->flatten($customer->get_data());
        foreach ($query as $row) {
            if (isset($flattenData[$row->wp_field])) {
                $customAttributes[] = [
                    'name' => $row->ecdp_field,
                    'value' => $flattenData[$row->wp_field],
                ];
            }
        }

        $customerApiData['customAttributes'] = $customAttributes;

        self::expertsender_cdp_add_or_update_customer($customerApiData, false);
    }

    function expertsender_cdp_edit_billing_address($user_id, $load_address)
    {
        if ($load_address == 'billing') {
            $customer = new WC_Customer($user_id);

            $customerApiData['email'] = $customer->get_email();
            $customerApiData['crmId'] = strval($customer->get_id());
            $customerApiData['firstName'] = $customer->get_first_name();
            $customerApiData['lastName'] = $customer->get_last_name();
            if (get_option('expertsender_cdp_enable_phone')) {
                $customerApiData['phone'] = $customer->get_billing_phone();
            }

            $customAttributes = [];

            global $wpdb;
            $table_name = $wpdb->prefix . 'expertsender_cdp_mappings';
            $query = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE resource_type = %s",
                    $this::RESOURCE_CUSTOMER
                )
            );
            $flattenData = $this->flatten($customer->get_data());

            foreach ($query as $row) {
                if (isset($flattenData[$row->wp_field])) {
                    $customAttributes[] = [
                        'name' => $row->ecdp_field,
                        'value' => $flattenData[$row->wp_field],
                    ];
                }
            }

            $customerApiData['customAttributes'] = $customAttributes;
            self::expertsender_cdp_add_or_update_customer($customerApiData);
        }
    }

    function expertsender_cdp_edit_shipping_address($user_id, $load_address)
    {
        if ($load_address == 'shipping') {
            $customer = new WC_Customer($user_id);

            $customerApiData['email'] = $customer->get_email();
            $customerApiData['crmId'] = strval($customer->get_id());
            $customerApiData['firstName'] = $customer->get_first_name();
            $customerApiData['lastName'] = $customer->get_last_name();
            if (get_option('expertsender_cdp_enable_phone')) {
                $customerApiData['phone'] = $customer->get_billing_phone();
            }

            $customAttributes = [];

            global $wpdb;
            $table_name = $wpdb->prefix . 'expertsender_cdp_mappings';
            $query = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE resource_type = %s",
                    $this::RESOURCE_CUSTOMER
                )
            );
            $flattenData = $this->flatten($customer->get_data());

            foreach ($query as $row) {
                if (isset($flattenData[$row->wp_field])) {
                    $customAttributes[] = [
                        'name' => $row->ecdp_field,
                        'value' => $flattenData[$row->wp_field],
                    ];
                }
            }

            $customerApiData['customAttributes'] = $customAttributes;
            self::expertsender_cdp_add_or_update_customer($customerApiData);
        }
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
}
