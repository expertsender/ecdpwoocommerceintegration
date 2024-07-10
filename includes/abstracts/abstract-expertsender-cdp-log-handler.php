<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class ExpertSender_CDP_Log_Handler implements ExpertSender_CDP_Log_Handler_Interface {
    /**
     * @param int $timestamp
     * @return string
     */
    protected static function format_time( $timestamp ) {
        return gmdate( 'c', $timestamp );
    }

    /**
     * @param int $timestamp
     * @param string $level
     * @param string $message
     * @param array $context
     * @return string
     */
    protected static function format_entry( $timestamp, $level, $message, $context ) {
        $time_string = self::format_time( $timestamp );
        $level_string = strtoupper( $level );
        $entry = "{$time_string} {$level_string} {$message}";

        return apply_filters(
            'expertsender_cdp_format_log_entry',
            $entry,
            array(
                'timestamp' => $timestamp,
                'level' => $level,
                'message' => $message,
                'context' => $context
            )
        );
    }
}