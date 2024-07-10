<?php

class ExpertSender_CDP_Inject_Consent
{
    public const CONSENT_INPUT_KEY = 'expertsender-cdp-consents';

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
            array( $this, 'expertsender_cdp_add_consents_in_customer_settings_form' ),
            9,
            1
        );

        add_action(
            'woocommerce_review_order_before_submit',
            array( $this, 'expertsender_cdp_add_consents_in_checkout_form' ),
            20
        );

    }

    /**
     * @return void
     */
    public function expertsender_cdp_add_consents_in_register_form() {
        $this->expertsender_cdp_add_consents( ExpertSender_CDP_Admin::FORM_REGISTRATION_KEY );
    }

    /**
     * @return void
     */
    public function expertsender_cdp_add_consents_in_customer_settings_form() {
        $this->expertsender_cdp_add_consents( ExpertSender_CDP_Admin::FORM_CUSTOMER_SETTINGS_KEY );
    }

    /**
     * @param array $checkout_fields
     * @return array
     */
    public function expertsender_cdp_add_consents_in_checkout_form( $checkout_fields ) {
        return $this->expertsender_cdp_add_consents( ExpertSender_CDP_Admin::FORM_CHECKOUT_KEY, $checkout_fields );
    }

    /**
     * @param string $form_location
     * @param mixed $returned_data
     * @return mixed|void
     */
    public function expertsender_cdp_add_consents( $form_location, $return_data = null )
    {
        $customer = new WC_Customer( get_current_user_id() );

        global $wpdb;
        $table_name = $wpdb->prefix . 'expertsender_cdp_consents';

        $consents = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE consent_location = %s",
                $form_location
            )
        );
        $consentsData = array();

        if ( ExpertSender_CDP_Admin::FORM_CUSTOMER_SETTINGS_KEY === $form_location ) {
            if ( count( $consents ) ) {
                $data = $this->expertsender_cdp_get_user_consents_from_api( $customer->get_email() );

                if ( $data != null && property_exists( $data, "consentsData" )
                    && property_exists( $data->consentsData, "consents" ) 
                ) {
                    foreach ( $data->consentsData->consents as $con ) {
                        $consentsData[ $con->id ] = $con->value;
                    }
                }
            }
        }

        if ( ! empty ($consents ) ) {
            if (
                ExpertSender_CDP_Admin::FORM_CUSTOMER_SETTINGS_KEY !== $form_location &&
                $text_before = get_option( 'expertsender_cdp_' . $form_location . '_text_before' )
            ) {
                echo '<div class="expertsender_cdp-text-before-consents">' . esc_html( $text_before ) . '</div>';
            };

            if ( ExpertSender_CDP_Admin::FORM_CUSTOMER_SETTINGS_KEY === $form_location ) {
                echo '<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide expertsender_cdp">';
                echo '<label class="expertsender_cdp">Zgody marketingowe</label>';
            }

            foreach ( $consents as $consent ) {
                $checked = isset( $consentsData[ $consent->api_consent_id ] ) &&
                    $consentsData[ $consent->api_consent_id ] != 'False' ? 1 : 0;

                $field_args = [
                    'type' => 'checkbox',
                    'name' => self::CONSENT_INPUT_KEY . "[{$consent->api_consent_id}]",
                    'label' => $consent->consent_text,
                    'class' => [ 'my-custom-class' ],
                    'default' => $checked,
                ];
                $key = self::CONSENT_INPUT_KEY . "[{$consent->api_consent_id}]";

                woocommerce_form_field( $key, $field_args );
            }

            if ( ExpertSender_CDP_Admin::FORM_CUSTOMER_SETTINGS_KEY === $form_location ) {
                echo '</p>';
            }
        }

        if ( null !== $return_data ) {
            return $return_data;
        }
    }

    public function expertsender_cdp_get_user_consents_from_api($email)
    {
        $api_url = ES_API_URL . 'customers/email/' . urlencode($email);

        $headers = [
            'accept' => 'application/json',
            'x-api-key' => get_option( ExpertSender_CDP_Admin::OPTION_API_KEY ),
        ];

        $args = [
            'headers' => $headers,
        ];

        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Coś poszło nie tak: $error_message";
            return null;
        } else {
            $response_body = wp_remote_retrieve_body($response);
            return json_decode($response_body)->data;
        }
    }
}
