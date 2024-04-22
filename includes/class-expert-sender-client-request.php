<?php

class Expert_Sender_Client_Request
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
         * defined in Expert_Sender_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Expert_Sender_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/expert-sender-public.css',
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
         * defined in Expert_Sender_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Expert_Sender_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/expert-sender-public.js',
            ['jquery'],
            $this->version,
            false
        );
    }

    public function expert_sender_create_customer(
        $customerId,
        $customerData,
        $password
    ) {
        $customerApiData = [];
        $customerApiData['email'] = $customerData['user_email'];
        $customerApiData['crmId'] = strval($customerId + 1000);
        $this->expert_sender_add_or_update_customer($customerApiData);
    }

    public function expert_sender_edit_customer($user_id)
    {
        $customer = new WC_Customer(get_current_user_id());

        global $wpdb;
        $table_name = $wpdb->prefix . 'expert_sender_consents';

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
        $customerApiData['crmId'] = strval($customer->get_id() + 1000);
        $customerApiData['firstName'] = $customer->get_first_name();
        $customerApiData['lastName'] = $customer->get_last_name();

        $consentApiDataArray = [];
        foreach ($consentValues as $key => $consentValue) {
            $consentApiData['id'] = $key;
            $consentApiData['value'] = $consentValue ? 'True' : 'False';
            $cosnentApiDataArray[] = $consentApiData;
        }
        $customerApiData['consentsData'] = [];
        $customerApiData['consentsData']['consents'] = $cosnentApiDataArray;
        // $customerApiData['consentsData']['force'] = true;
        // $customerApiData['consentsData']['confirmationMessageId'] = 0;

        $this->expert_sender_add_or_update_customer($customerApiData);
    }

    function expert_sender_edit_billing_address($user_id, $load_address)
    {
        if ($load_address == 'billing') {
            $customer = new WC_Customer($user_id);
            $customerApiData['email'] = $customer->get_email();
            $customerApiData['crmId'] = strval($customer->get_id() + 1000);
            $customerApiData['firstName'] = $customer->get_first_name();
            $customerApiData['lastName'] = $customer->get_last_name();
            if (get_option('expert_sender_enable_script')) {
                $customerApiData['phone'] = $customer->get_billing_phone();
            }
            $this->expert_sender_add_or_update_customer($customerApiData);
        }
    }

    public function expert_sender_add_or_update_customer($customerData)
    {
        $url = 'https://api.ecdp.app/customers';

        $body = json_encode([
            'mode' => 'AddOrUpdate',
            'matchBy' => 'Email',
            'data' => [$customerData],
        ]);

        global $wpdb;

        $table_name = $wpdb->prefix . 'expert_sender_requests';

        $wpdb->insert($table_name, [
            'created_at' => current_time('mysql'),
            'is_sent' => false,
            'url_address' => $url,
            'json_body' => $body,
            'resource_type' => 'customer',
            'resource_id' => 1,
        ]);
    }
}
