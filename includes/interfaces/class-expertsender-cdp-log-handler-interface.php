<?php

interface ExpertSender_CDP_Log_Handler_Interface {
    /**
     * @param int $timestamp
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function handle( $timestamp, $level, $message, $context );
}
