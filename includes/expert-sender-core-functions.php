<?php

/**
 * @return \Expert_Sender_Logger
 */
function expert_sender_get_logger() {
    static $logger = null;

    if (null === $logger) {
        $logger = new Expert_Sender_Logger();
    }

    return $logger;
}