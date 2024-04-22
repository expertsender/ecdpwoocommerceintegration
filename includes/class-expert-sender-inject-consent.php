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
        add_filter( 'woocommerce_edit_account_form_fields', array( $this, 'expert_sender_add_consents' ), 9 , 1 );
    }

    public function expert_sender_add_consents(){
        		$customer = new WC_Customer( get_current_user_id() );

                global $wpdb;
                $table_name = $wpdb->prefix . 'expert_sender_consents';
        
                $consents = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM $table_name WHERE consent_location = %s",
                        'customer_settings'
                    )
                );
            if(count($consents)){
                //TODO: Get user consents and change default value to one from expert sender
            }

            foreach($consents as $consent){

                $field_args = array(
                    'type'          => 'checkbox',
                    'name'          => 'consent[' . $consent->api_consent_id . "]",
                    'label'         => $consent->consent_text,
                    'class'         => array('my-custom-class'),
                    'default'         => 0
                );
                $key = 'consent[' . $consent->api_consent_id . "]";
                woocommerce_form_field($key, $field_args );
            }
    }
}
