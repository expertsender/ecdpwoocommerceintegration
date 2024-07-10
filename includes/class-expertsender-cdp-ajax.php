<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ExpertSender_CDP_Ajax {
    const REQUEST_UPDATE_NEWSLETTER_CONSENTS = 'expertsender_cdp_update_newsletter_consents';

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
        $consents_data = ExpertSender_CDP_Client_Request::get_consents_from_request(
            ExpertSender_CDP_Admin::FORM_NEWSLETTER_KEY
        );
        
        if ( ( $email = $_POST['email'] ?? false ) && ! empty( $consents_data ) ) {
            ExpertSender_CDP_Client_Request::expertsender_cdp_add_or_update_customer(
                array(
                    'email' => $email,
                    'consentsData' => $consents_data
                )
            );
        }
        
        wp_send_json_success( null, 200 );
    }
}
