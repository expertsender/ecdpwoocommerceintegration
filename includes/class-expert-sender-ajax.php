<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Expert_Sender_Ajax {
    const REQUEST_UPDATE_NEWSLETTER_CONSENTS = 'expert_sender_update_newsletter_consents';

    public function __construct() {
        add_action(
            'wp_ajax_nopriv_' . self::REQUEST_UPDATE_NEWSLETTER_CONSENTS,
            array( $this, 'update_newsletter_consents' )
        );
        add_action(
            'wp_ajax_' . self::REQUEST_UPDATE_NEWSLETTER_CONSENTS,
            array( $this, 'update_newsletter_consents' )
        );
    }

    /**
     * @return void
     */
    public function update_newsletter_consents() {
        $consents_data = Expert_Sender_Client_Request::get_consents_from_request(
            Expert_Sender_Admin::FORM_NEWSLETTER_KEY
        );
        
        if ( ( $email = $_POST['email'] ?? false ) && ! empty( $consents_data ) ) {
            Expert_Sender_Client_Request::expert_sender_add_or_update_customer(
                array(
                    'email' => $email,
                    'consentsData' => $consents_data
                )
            );
        }
        
        wp_send_json_success( null, 200 );
    }
}
