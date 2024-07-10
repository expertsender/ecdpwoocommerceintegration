<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ExpertSender_CDP_Log_Handler_File extends ExpertSender_CDP_Log_Handler
{
    /**
     * @var array
     */
    protected $handles = array();

    /**
     * @param string $handle
     * @return string|false
     */
    public static function get_log_file_path( $handle ) {
        $log_directory = ExpertSender_CDP_Logging_Util::get_log_directory();

        if ( null === $log_directory ) {
            return false;
        }

        if ( function_exists( 'wp_hash' ) ) {
            return trailingslashit( $log_directory ) . self::get_log_file_name( $handle );
        }

        _doing_it_wrong(__METHOD__, __( 'This method should not be called before plugins_loaded.', 'expertsender_cdp'), '' );
        return false;
    }

    /**
     * @param string handle
     * @return string|false
     */
    public static function get_log_file_name( $handle ) {
        if ( function_exists( 'wp_hash' ) ) {
			$date_suffix = date( 'Y-m-d', time() );
			$hash_suffix = wp_hash( $handle );
			return sanitize_file_name( implode( '-', array( $handle, $date_suffix, $hash_suffix ) ) . '.log' );
		} else {
			_doing_it_wrong( __METHOD__, __( 'This method should not be called before plugins_loaded.', 'woocommerce' ), '' );
			return false;
		}
    }

    /**
     * {@inheritdoc}
     */
    public function handle($timestamp, $level, $message, $context)
    {
        if ( isset( $context['source'] ) && $context['source'] ) {
			$handle = $context['source'];
		} else {
			$handle = 'log';
		}

        $entry = self::format_entry( $timestamp, $level, $message, $context );

        return $this->add( $entry, $handle );
    }

    /**
     * @param string $entry
     * @param string $handle
     * @return bool
     */
    protected function add( $entry, $handle ) {
        $result = false;

        if ( $this->open( $handle ) && is_resource( $this->handles[ $handle ] ) ) {
            $result = fwrite( $this->handles[ $handle ], $entry . PHP_EOL );
        }

        return false !== $result;
    }

    /**
     * @param string $handle
     * @param string $mode
     * @return bool
     */
    protected function open( $handle, $mode = 'a' ) {
        if ( $this->is_open( $handle ) ) {
            return true;
        }

        $file = self::get_log_file_path( $handle );

        if ( $file ) {
            if (! file_exists( $file ) ) {
                $temphandle = fopen( $file, 'w+' );
                if ( is_resource( $temphandle ) ) {
                    fclose( $temphandle );
                }
            }

            $resource = fopen( $file, $mode );

            if ( $resource ) {
                $this->handles[ $handle ] = $resource;
                return true;
            }
        }

        return false;
    }

    /**
	 * @param string $handle
	 * @return bool
	 */
	protected function is_open( $handle ) {
		return array_key_exists( $handle, $this->handles ) && is_resource( $this->handles[ $handle ] );
	}
}
