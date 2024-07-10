<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ExpertSender_CDP_Api {
    /**
     * @param array|WP_Error $response
     * @param string $context
     * @param string $class
     * @param array $parsed_args
     * @param string $url
     * @return void
     */
    public function log_request( $response, $context, $class, $parsed_args, $url ) {
        if (false !== strpos( $url, 'api.ecdp.app' ) ) {
            $logger = expertsender_cdp_get_logger();
            $body_text = null !== $parsed_args['body'] ? " Body: {$parsed_args['body']}." : '';
            $response_text = $response instanceof WP_Error ? print_r( $response->get_error_messages(), true )
                : print_r( $response['body'] , true );
            $response_code = $response instanceof WP_Error ? 500 : $response['response']['code'];
            $message = "Request {$response_code} {$parsed_args['method']} {$url}.{$body_text}";

            if ( ! empty ( $response_text ) ) {
                $message .= " Response body: {$response_text}";
            }

            $logger->debug( $message, array( 'source' => 'api' ) );
        }
    }
}