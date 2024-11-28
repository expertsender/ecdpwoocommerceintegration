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
        $customer_id,
        $customer_data
    ) {
        $customer_api_data = array();
        $customer_api_data['email'] = $customer_data['user_email'];
        $customer_api_data['crmId'] = strval( $customer_id );
        $consents_data = self::get_consents_from_request( ExpertSender_CDP_Admin::FORM_REGISTRATION_KEY );
        $customer_api_data['consentsData'] = ! empty ( $consents_data ) ? $consents_data : null;
        es_cdp_add_or_update_customer( $customer_api_data );
    }

    /**
     * @param int $user_id
     *
     * @return void
     */
    public function expertsender_cdp_edit_customer( $user_id ) {
        $customer_api_data = $this->get_customer_data( $user_id );
        $consents_data = self::get_consents_from_request( ExpertSender_CDP_Admin::FORM_CUSTOMER_SETTINGS_KEY );
        $customer_api_data['consentsData'] = ! empty ( $consents_data ) ? $consents_data : null;
        es_cdp_add_or_update_customer( $customer_api_data, false );
    }

    /**
     * @param int $user_id
     * @param string $load_address
     *
     * @return void
     */
    public function expertsender_cdp_edit_billing_address( $user_id, $load_address ) {
        if ( $load_address == 'billing' ) {
            $customer_api_data = $this->get_customer_data( $user_id );
            es_cdp_add_or_update_customer( $customer_api_data );
        }
    }

    /**
     * @param int $user_id
     * @param string $load_address
     *
     * @return void
     */
    public function expertsender_cdp_edit_shipping_address( $user_id, $load_address ) {
        if ( $load_address == 'shipping' ) {
            $customer_api_data = $this->get_customer_data( $user_id );
            es_cdp_add_or_update_customer( $customer_api_data );
        }
    }

    /**
     * @param int $user_id
     *
     * @return array
     */
    private function get_customer_data( $user_id ) {
        $customer_api_data = array();
        $customer = new WC_Customer( $user_id );

        $customer_api_data['email'] = $customer->get_email();
        $customer_api_data['crmId'] = strval( $customer->get_id() );
        $customer_api_data['firstName'] = $customer->get_first_name();
        $customer_api_data['lastName'] = $customer->get_last_name();
        
        if ( get_option('expertsender_cdp_enable_phone') ) {
            $customer_api_data['phone'] = $customer->get_billing_phone();
        }

        $custom_attributes = es_get_mapped_attributes( $customer, self::RESOURCE_CUSTOMER );

        if ( ! empty( $custom_attributes ) ) {
            $customer_api_data['customAttributes'] = $custom_attributes;
        }

        return $customer_api_data;
    }
}
