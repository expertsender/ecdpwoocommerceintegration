<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Expert_Sender_Api {
    /**
     * @param array|WP_Error $response
     * @param string $context
     * @param string $class
     * @param array $parsed_args
     * @param string $url
     */
    public function log_request( $response, $context, $class, $parsed_args, $url ) {
        if (false !== strpos( $url, 'api.ecdp.app' ) ) {
            $logger = expert_sender_get_logger();
            $body_text = null !== $parsed_args['body'] ? " Body: {$parsed_args['body']}." : '';
            $response_text = $response instanceof WP_Error ? print_r( $response->get_error_messages(), true )
                : print_r( $response['body'] , true );
            $message = "Request {$parsed_args['method']} {$url}.{$body_text} Response: {$response_text}";
            $logger->debug( $message, array( 'source' => 'api' ) );
        }
    }
}