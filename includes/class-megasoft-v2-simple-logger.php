<?php
/**
 * MegaSoft Simple Logger - Sistema de logs simplificado y confiable
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MegaSoft_V2_Simple_Logger {

    private $enabled;
    private $log_file;

    public function __construct( $enabled = true ) {
        $this->enabled = $enabled;
        $this->log_file = MEGASOFT_V2_PLUGIN_PATH . 'megasoft-debug.log';
    }

    /**
     * Log info message
     */
    public function info( $message, $context = array() ) {
        $this->log( 'INFO', $message, $context );
    }

    /**
     * Log error message
     */
    public function error( $message, $context = array() ) {
        $this->log( 'ERROR', $message, $context );
    }

    /**
     * Log warning message
     */
    public function warn( $message, $context = array() ) {
        $this->log( 'WARN', $message, $context );
    }

    /**
     * Log debug message
     */
    public function debug( $message, $context = array() ) {
        $this->log( 'DEBUG', $message, $context );
    }

    /**
     * Main log method
     */
    private function log( $level, $message, $context = array() ) {
        if ( ! $this->enabled ) {
            return false;
        }

        $timestamp = current_time( 'mysql' );
        $order_id = $context['order_id'] ?? null;
        $control = $context['control'] ?? $context['control_number'] ?? null;

        // Sanitize sensitive data
        $context = $this->sanitize_sensitive_data( $context );

        // Log to file
        $this->log_to_file( $level, $message, $context, $timestamp );

        // Log to database
        $this->log_to_database( $level, $message, $context, $timestamp, $order_id, $control );

        return true;
    }

    /**
     * Log to file
     */
    private function log_to_file( $level, $message, $context, $timestamp ) {
        $log_line = sprintf(
            "[%s] [%s] %s\n",
            $timestamp,
            $level,
            $message
        );

        if ( ! empty( $context ) ) {
            $log_line .= "Context: " . json_encode( $context, JSON_UNESCAPED_UNICODE ) . "\n";
        }

        $log_line .= str_repeat( '-', 80 ) . "\n";

        // Write to file
        file_put_contents( $this->log_file, $log_line, FILE_APPEND );
    }

    /**
     * Log to database
     */
    private function log_to_database( $level, $message, $context, $timestamp, $order_id, $control ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_logs';

        // Verify table exists first
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

        if ( ! $table_exists ) {
            // Table doesn't exist, log to file
            $this->log_to_file( 'ERROR', "Table $table_name does not exist", array() );
            return false;
        }

        // Insert log
        $result = $wpdb->insert(
            $table_name,
            array(
                'order_id'       => $order_id,
                'control_number' => $control,
                'level'          => strtoupper( $level ),
                'message'        => $message,
                'context'        => json_encode( $context, JSON_UNESCAPED_UNICODE ),
                'created_at'     => $timestamp,
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( $result === false ) {
            // Log DB error to file
            $this->log_to_file( 'ERROR', "Failed to insert log into DB: " . $wpdb->last_error, array() );
            return false;
        }

        return true;
    }

    /**
     * Sanitize sensitive data
     */
    private function sanitize_sensitive_data( $context ) {
        $sensitive_keys = array( 'pan', 'cvv2', 'cvv', 'expdate', 'api_password', 'password' );

        foreach ( $sensitive_keys as $key ) {
            if ( isset( $context[ $key ] ) ) {
                $context[ $key ] = '***REDACTED***';
            }
        }

        return $context;
    }

    /**
     * Get log file path
     */
    public function get_log_file() {
        return $this->log_file;
    }

    /**
     * Clear log file
     */
    public function clear_file() {
        if ( file_exists( $this->log_file ) ) {
            unlink( $this->log_file );
        }
    }

    /**
     * Read log file
     */
    public function read_file( $lines = 500 ) {
        if ( ! file_exists( $this->log_file ) ) {
            return '';
        }

        $content = file_get_contents( $this->log_file );

        // Get last N lines
        $lines_array = explode( "\n", $content );
        $lines_array = array_slice( $lines_array, -$lines );

        return implode( "\n", $lines_array );
    }
}
