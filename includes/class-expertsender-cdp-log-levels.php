<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ExpertSender_CDP_Log_Levels {
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    /**
     * @var array
     */
    protected static $level_to_severity = array(
		self::EMERGENCY => 800,
		self::ALERT     => 700,
		self::CRITICAL  => 600,
		self::ERROR     => 500,
		self::WARNING   => 400,
		self::NOTICE    => 300,
		self::INFO      => 200,
		self::DEBUG     => 100,
	);

    /**
     * @param string $level
     * @return bool
     */
    public static function is_valid_level( $level ) {
		return is_string( $level ) && array_key_exists( strtolower( $level ), self::$level_to_severity );
	}
}
