<?php

class Expert_Sender_Inject_Consent
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
        add_filter(
            'woocommerce_edit_account_form_fields',
            [$this, 'expert_sender_add_consents'],
            9,
            1
        );
    }

    public function expert_sender_add_consents()
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
        $consentsData = [];

        if (count($consents)) {
            $data = $this->expert_sender_get_user_consents_from_api(
                $customer->get_email()
            );
            if ($data->consentsData && $data->consentsData->consents) {
                foreach ($data->consentsData->consents as $con) {
                    $consentsData[$con->id] = $con->value;
                }
            }
        }

        foreach ($consents as $consent) {
            $checked =
                isset($consentsData[$consent->api_consent_id]) &&
                $consentsData[$consent->api_consent_id] != 'False'
                    ? 1
                    : 0;

            $field_args = [
                'type' => 'checkbox',
                'name' => 'consent[' . $consent->api_consent_id . ']',
                'label' => $consent->consent_text,
                'class' => ['my-custom-class'],
                'default' => $checked,
            ];
            $key = 'consent[' . $consent->api_consent_id . ']';
            woocommerce_form_field($key, $field_args);
        }
    }

    public function expert_sender_get_user_consents_from_api($email)
    {
        $api_url = 'https://api.ecdp.app/customers/email/' . urlencode($email);

        $headers = [
            'accept' => 'application/json',
            'x-api-key' => get_option('expert_sender_key'),
        ];

        $args = [
            'headers' => $headers,
        ];

        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $response_body = wp_remote_retrieve_body($response);
            return json_decode($response_body)->data;
        }
    }
}
