<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Expert_Sender_Logging_Util {
    /**
     * @return bool
     */
    public static function is_logging_enabled() {
        return (bool) get_option(Expert_Sender_Admin::OPTION_ENABLE_LOGS);
    }

    /**
     * @return Expert_Sender_Log_Handler_File
     */
    public static function get_default_handler() {
        return new Expert_Sender_Log_Handler_File();
    }

    /**
     * @return string
     */
    public static function get_log_directory() {
        /** @var \WP_Filesystem_Base $wp_filesystem */
        global $wp_filesystem;
        WP_Filesystem();

        $dir = apply_filters(
            'expert_sender_log_directory',
            wp_upload_dir()['basedir'] . '/expert-sender-logs/'
        );

        $dir = trailingslashit( $dir );
        $realpath = realpath( $dir );

        if ( false === $realpath ) {
            $result = wp_mkdir_p( $dir );

            if ( true === $result ) {
                try {
                    $wp_filesystem->put_contents( $dir . '.htaccess', 'deny from all' );
                    $wp_filesystem->put_contents( $dir . 'index.html', '' );
                } catch ( \Exception $ex ) {
                   // Creation failed
                }
            }
        }

        return $dir;
    }
}
