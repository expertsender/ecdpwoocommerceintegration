<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ExpertSender_CDP_Logging_Util {
    /**
     * @return bool
     */
    public static function is_logging_enabled() {
        return (bool) get_option( ExpertSender_CDP_Admin::OPTION_ENABLE_LOGS );
    }

    /**
     * @return ExpertSender_CDP_Log_Handler_File
     */
    public static function get_default_handler() {
        return new ExpertSender_CDP_Log_Handler_File();
    }

    /**
     * @return string|null
     */
    public static function get_log_directory() {
        /** @var \WP_Filesystem_Base $wp_filesystem */
        global $wp_filesystem;
        
        if ( null === $wp_filesystem && function_exists( 'WP_Filesystem' ) ) {
            WP_Filesystem();

            if ( null === $wp_filesystem ) {
                return null;
            }
        }

        $dir = apply_filters(
            'expertsender_cdp_log_directory',
            wp_upload_dir()[ 'basedir' ] . '/expertsender-cdp-logs/'
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
