<?php

interface Expert_Sender_Logger_Interface {

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log( $level, $message, $context = array() );

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error( $message, $context = array() );

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug( $message, $context = array() );
}
