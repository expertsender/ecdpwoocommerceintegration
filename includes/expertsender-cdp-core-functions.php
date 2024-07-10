<?php

defined ( 'ABSPATH' ) || exit;

/**
 * @return \ExpertSender_CDP_Logger
 */
function expertsender_cdp_get_logger() {
    static $logger = null;

    if (null === $logger) {
        $logger = new ExpertSender_CDP_Logger();
    }

    return $logger;
}