<?php
/**
 * MegaSoft Logger Class v2
 * Maneja todos los logs del plugin con diferentes niveles y almacenamiento en BD
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MegaSoft_V2_Logger {

    private $enabled;
    private $level;
    private $wc_logger;

    const LEVEL_DEBUG = 0;
    const LEVEL_INFO  = 1;
    const LEVEL_WARN  = 2;
    const LEVEL_ERROR = 3;

    private $levels = array(
        'debug' => self::LEVEL_DEBUG,
        'info'  => self::LEVEL_INFO,
        'warn'  => self::LEVEL_WARN,
        'error' => self::LEVEL_ERROR,
    );

    public function __construct( $enabled = false, $level = 'info' ) {
        $this->enabled = $enabled;
        $this->level = $this->levels[ $level ] ?? self::LEVEL_INFO;

        if ( function_exists( 'wc_get_logger' ) ) {
            $this->wc_logger = wc_get_logger();
        }
    }

    public function debug( $message, $context = array() ) {
        $this->log( 'debug', $message, $context );
    }

    public function info( $message, $context = array() ) {
        $this->log( 'info', $message, $context );
    }

    public function warn( $message, $context = array() ) {
        $this->log( 'warn', $message, $context );
    }

    public function error( $message, $context = array() ) {
        $this->log( 'error', $message, $context );
    }

    private function log( $level, $message, $context = array() ) {
        if ( ! $this->enabled ) {
            return;
        }

        $level_int = $this->levels[ $level ] ?? self::LEVEL_INFO;

        // Solo loguear si el nivel es >= al configurado
        if ( $level_int < $this->level ) {
            return;
        }

        // Sanitizar datos sensibles del contexto
        $context = $this->sanitize_sensitive_data( $context );

        // Preparar contexto
        $log_context = array_merge( array(
            'timestamp' => current_time( 'mysql' ),
            'level'     => strtoupper( $level ),
            'source'    => 'megasoft-gateway-v2'
        ), $context );

        // Formatear mensaje
        $formatted_message = $this->format_message( $message, $log_context );

        // Log en WooCommerce
        if ( $this->wc_logger ) {
            $this->wc_logger->log( $level, $formatted_message, $log_context );
        }

        // Log en base de datos personalizada
        $this->log_to_database( $level, $message, $context );

        // Log crítico adicional
        if ( $level === 'error' ) {
            $this->log_critical_error( $message, $context );
        }
    }

    /**
     * Sanitizar datos sensibles antes de guardar en logs
     * PCI DSS Compliance: NUNCA guardar PAN, CVV, expdate completos
     */
    private function sanitize_sensitive_data( $context ) {
        $sensitive_keys = array( 'pan', 'cvv2', 'cvv', 'expdate', 'api_password', 'password', 'security_key' );

        foreach ( $sensitive_keys as $key ) {
            if ( isset( $context[ $key ] ) ) {
                // Enmascarar completamente
                $context[ $key ] = '***REDACTED***';
            }
        }

        // Si hay tarjeta, solo mostrar últimos 4 dígitos
        if ( isset( $context['tarjeta'] ) && strlen( $context['tarjeta'] ) > 4 ) {
            $last_four = substr( $context['tarjeta'], -4 );
            $context['tarjeta'] = '****' . $last_four;
        }

        return $context;
    }

    private function format_message( $message, $context ) {
        $formatted = "[{$context['timestamp']}] [{$context['level']}] {$message}";

        if ( isset( $context['order_id'] ) ) {
            $formatted .= " [Order: #{$context['order_id']}]";
        }

        if ( isset( $context['control_number'] ) ) {
            $formatted .= " [Control: {$context['control_number']}]";
        }

        return $formatted;
    }

    private function log_to_database( $level, $message, $context ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_logs';

        $result = $wpdb->insert(
            $table_name,
            array(
                'order_id'       => $context['order_id'] ?? null,
                'control_number' => $context['control_number'] ?? null,
                'level'          => strtoupper( $level ),
                'message'        => $message,
                'context'        => json_encode( $context ),
                'created_at'     => current_time( 'mysql' )
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );

        // If insert failed, log to WordPress error log
        if ( $result === false && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( "[MEGASOFT V2 DB ERROR] Failed to insert log into database. Error: " . $wpdb->last_error );
        }
    }

    private function log_critical_error( $message, $context ) {
        // Enviar email al administrador para errores críticos
        $admin_email = get_option( 'admin_email' );

        if ( $admin_email && apply_filters( 'megasoft_v2_send_error_emails', true ) ) {
            $subject = __( 'Error Crítico - Mega Soft Gateway v2', 'woocommerce-megasoft-gateway-v2' );
            $body = sprintf(
                __( "Se ha producido un error crítico en el gateway de Mega Soft v2:\n\nMensaje: %s\n\nContexto: %s\n\nFecha: %s", 'woocommerce-megasoft-gateway-v2' ),
                $message,
                print_r( $context, true ),
                current_time( 'mysql' )
            );

            wp_mail( $admin_email, $subject, $body );
        }

        // Log en archivo de errores de WordPress si está disponible
        if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( "[MEGASOFT V2 ERROR] {$message} " . json_encode( $context ) );
        }
    }

    public function get_logs( $limit = 100, $level = null, $order_id = null ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_logs';
        $where_conditions = array( '1=1' );
        $where_values = array();

        if ( $level ) {
            $where_conditions[] = 'level = %s';
            $where_values[] = strtoupper( $level );
        }

        if ( $order_id ) {
            $where_conditions[] = 'order_id = %d';
            $where_values[] = $order_id;
        }

        $where_clause = implode( ' AND ', $where_conditions );
        $where_values[] = $limit;

        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name}
             WHERE {$where_clause}
             ORDER BY created_at DESC
             LIMIT %d",
            $where_values
        );

        return $wpdb->get_results( $query );
    }

    public function clear_old_logs( $days = 30 ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_logs';

        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$table_name}
             WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ) );

        $this->info( "Limpieza de logs: {$deleted} registros eliminados." );

        return $deleted;
    }

    public function export_logs( $start_date = null, $end_date = null ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_logs';
        $where_conditions = array( '1=1' );
        $where_values = array();

        if ( $start_date ) {
            $where_conditions[] = 'created_at >= %s';
            $where_values[] = $start_date;
        }

        if ( $end_date ) {
            $where_conditions[] = 'created_at <= %s';
            $where_values[] = $end_date;
        }

        $where_clause = implode( ' AND ', $where_conditions );

        $query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC";

        if ( ! empty( $where_values ) ) {
            $query = $wpdb->prepare( $query, $where_values );
        }

        $logs = $wpdb->get_results( $query );

        // Crear archivo CSV
        $filename = 'megasoft-v2-logs-' . date( 'Y-m-d-H-i-s' ) . '.csv';
        $filepath = wp_upload_dir()['path'] . '/' . $filename;

        $fp = fopen( $filepath, 'w' );

        // Headers
        fputcsv( $fp, array( 'ID', 'Order ID', 'Control Number', 'Level', 'Message', 'Created At' ) );

        // Data
        foreach ( $logs as $log ) {
            fputcsv( $fp, array(
                $log->id,
                $log->order_id,
                $log->control_number,
                $log->level,
                $log->message,
                $log->created_at
            ) );
        }

        fclose( $fp );

        return array(
            'success'  => true,
            'filename' => $filename,
            'url'      => wp_upload_dir()['url'] . '/' . $filename,
            'count'    => count( $logs )
        );
    }
}
