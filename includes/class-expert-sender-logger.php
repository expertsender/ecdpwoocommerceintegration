<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Expert_Sender_Logger implements Expert_Sender_Logger_Interface {
    /**
     * @var array
     */
    protected $handlers;

    /**
     * @param array|null $handlers
     */
    public function __construct( $handlers = null ) {
        if ( is_array( $handlers )) {
            $this->handlers = $handlers;
        }
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, $context = array())
    {
        if ( ! Expert_Sender_Log_Levels::is_valid_level( $level )) {
            error_log( sprintf( __( 'ExpertSender logger was called with an invalid level "%1$s".', 'expert-sender'), $level ) );
        }

        if ( $this->should_handle() ) {
            $timestamp = time();

            foreach ( $this->get_handlers() as $handler ) {
                $message = apply_filters( 'expert_sender_logger_log_message', $message, $level, $context, $handler );

                if ( null !== $message ) {
                    $handler->handle( $timestamp, $level, $message, $context );
                }
            }
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, $context = array())
    {
        $this->log(Expert_Sender_Log_Levels::ERROR, $message, $context );
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, $context = array())
    {
        $this->log(Expert_Sender_Log_Levels::DEBUG, $message, $context );
    }

    /**
     * @param string $level
     * @return bool
     */
    protected function should_handle() {
        return Expert_Sender_Logging_Util::is_logging_enabled();
    }

    /**
     * @return Expert_Sender_Log_Handler_Interface[]
     */
    protected function get_handlers() {
        if ( ! is_null ( $this->handlers ) ) {
            $handlers = $this->handlers;
        } else {
            $default_handler = Expert_Sender_Logging_Util::get_default_handler();
            $handlers = apply_filters( 'expert_sender_register_log_handlers', array( $default_handler ) );
        }

        $registered_handlers = array();

        if ( ! empty( $handlers ) && is_array( $handlers ) ) {
            foreach ( $handlers as $handler ) {
                if ( $handler instanceof Expert_Sender_Log_Handler_Interface ) {
                    $registered_handlers[] = $handler;
                } else {
                    _doing_it_wrong(
                        __METHOD__,
                        sprintf(
                            __('Provided handler %1$s does not implement %2$s', 'expert-sender' ),
                            esc_html( is_object( $handler ) ? get_class( $handler ) : $handler ),
                            'Expert_Sender_Log_Handler_Interface'
                        ),
                        ''
                    );
                }
            }
        }

        return $registered_handlers;
    }
}