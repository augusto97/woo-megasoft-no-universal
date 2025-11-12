<?php
/**
 * MegaSoft Gateway v2 - Webhook Handler
 *
 * Handles asynchronous notifications from Mega Soft payment gateway
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MegaSoft_V2_Webhook {

    /**
     * @var MegaSoft_V2_Logger
     */
    private $logger;

    /**
     * @var MegaSoft_V2_API
     */
    private $api;

    /**
     * Webhook endpoint slug
     */
    const WEBHOOK_ENDPOINT = 'megasoft-v2-webhook';

    /**
     * Mega Soft server IPs (whitelist)
     * Update with actual Mega Soft IPs
     */
    private $allowed_ips = array(
        '200.44.32.12',      // Example IP - update with real Mega Soft IPs
        '200.44.32.13',
        '127.0.0.1',         // Localhost for testing
        '::1',               // IPv6 localhost
    );

    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new MegaSoft_V2_Logger( true, 'info' );

        // Register webhook endpoint
        add_action( 'init', array( $this, 'add_webhook_endpoint' ) );
        add_action( 'template_redirect', array( $this, 'handle_webhook_request' ) );

        // Schedule cron for processing failed webhooks
        add_action( 'megasoft_v2_process_pending_webhooks', array( $this, 'process_pending_webhooks' ) );

        if ( ! wp_next_scheduled( 'megasoft_v2_process_pending_webhooks' ) ) {
            wp_schedule_event( time(), 'hourly', 'megasoft_v2_process_pending_webhooks' );
        }
    }

    /**
     * Add webhook rewrite endpoint
     */
    public function add_webhook_endpoint() {
        add_rewrite_endpoint( self::WEBHOOK_ENDPOINT, EP_ROOT );
    }

    /**
     * Handle incoming webhook request
     */
    public function handle_webhook_request() {
        global $wp_query;

        // Check if this is a webhook request
        if ( ! isset( $wp_query->query_vars[ self::WEBHOOK_ENDPOINT ] ) ) {
            return;
        }

        // Get request method
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ( $method !== 'POST' ) {
            $this->send_response( 405, 'Method Not Allowed' );
            return;
        }

        $this->logger->info( 'Webhook recibido', array(
            'ip'     => $this->get_client_ip(),
            'method' => $method,
        ) );

        try {
            // Verify IP whitelist
            if ( ! $this->verify_ip() ) {
                throw new Exception( 'IP no autorizada' );
            }

            // Get raw POST data
            $raw_body = file_get_contents( 'php://input' );

            if ( empty( $raw_body ) ) {
                throw new Exception( 'Body vacío' );
            }

            // Parse XML
            $xml = $this->parse_xml( $raw_body );

            // Validate signature
            if ( ! $this->verify_signature( $xml, $raw_body ) ) {
                throw new Exception( 'Firma inválida' );
            }

            // Process webhook
            $result = $this->process_webhook( $xml );

            if ( $result['success'] ) {
                $this->send_response( 200, 'OK', $result );
            } else {
                $this->send_response( 400, 'Bad Request', $result );
            }

        } catch ( Exception $e ) {
            $this->logger->error( 'Error en webhook', array(
                'error' => $e->getMessage(),
                'ip'    => $this->get_client_ip(),
            ) );

            $this->send_response( 400, 'Bad Request', array(
                'success' => false,
                'message' => $e->getMessage(),
            ) );
        }
    }

    /**
     * Parse XML from webhook
     *
     * @param string $xml_string XML string
     * @return SimpleXMLElement
     * @throws Exception
     */
    private function parse_xml( $xml_string ) {
        libxml_use_internal_errors( true );

        $xml = simplexml_load_string( $xml_string );

        if ( $xml === false ) {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            throw new Exception( 'XML inválido: ' . json_encode( $errors ) );
        }

        return $xml;
    }

    /**
     * Verify webhook signature
     *
     * @param SimpleXMLElement $xml XML object
     * @param string $raw_body Raw body
     * @return bool
     */
    private function verify_signature( $xml, $raw_body ) {
        // Get signature from XML
        $received_signature = (string) ( $xml->firma ?? '' );

        if ( empty( $received_signature ) ) {
            $this->logger->warn( 'Webhook sin firma', array(
                'ip' => $this->get_client_ip(),
            ) );
            // Allow for now, but log warning
            return true;
        }

        // Get security key from settings
        $settings = get_option( 'woocommerce_megasoft_v2_settings', array() );
        $security_key = $settings['security_key'] ?? '';

        if ( empty( $security_key ) ) {
            return true; // Can't verify without key
        }

        // Calculate expected signature
        // This is an example - adjust according to Mega Soft's signature algorithm
        $expected_signature = hash_hmac( 'sha256', $raw_body, $security_key );

        return hash_equals( $expected_signature, $received_signature );
    }

    /**
     * Process webhook notification
     *
     * @param SimpleXMLElement $xml XML object
     * @return array Result
     */
    private function process_webhook( $xml ) {
        $notification_type = (string) ( $xml->tipo ?? 'transaction' );

        $this->logger->info( 'Procesando webhook', array(
            'type' => $notification_type,
        ) );

        switch ( $notification_type ) {
            case 'transaction':
            case 'payment':
                return $this->process_transaction_notification( $xml );

            case 'refund':
            case 'anulacion':
                return $this->process_refund_notification( $xml );

            case 'chargeback':
                return $this->process_chargeback_notification( $xml );

            default:
                $this->logger->warn( 'Tipo de notificación desconocido', array(
                    'type' => $notification_type,
                ) );

                return array(
                    'success' => false,
                    'message' => 'Tipo de notificación no soportado',
                );
        }
    }

    /**
     * Process transaction notification
     *
     * @param SimpleXMLElement $xml XML object
     * @return array Result
     */
    private function process_transaction_notification( $xml ) {
        global $wpdb;

        $control = (string) ( $xml->control ?? '' );
        $codigo = (string) ( $xml->codigo ?? '' );
        $mensaje = (string) ( $xml->mensaje ?? '' );
        $autorizacion = (string) ( $xml->autorizacion ?? '' );
        $monto = (string) ( $xml->monto ?? '' );

        if ( empty( $control ) ) {
            throw new Exception( 'Número de control no proporcionado' );
        }

        $this->logger->info( 'Notificación de transacción', array(
            'control'      => $control,
            'codigo'       => $codigo,
            'autorizacion' => $autorizacion,
        ) );

        // Find order by control number
        $orders = wc_get_orders( array(
            'meta_key'   => '_megasoft_v2_control',
            'meta_value' => $control,
            'limit'      => 1,
        ) );

        if ( empty( $orders ) ) {
            // Save for later processing
            $this->save_failed_webhook( $xml, 'Orden no encontrada' );

            return array(
                'success' => false,
                'message' => 'Orden no encontrada',
            );
        }

        $order = $orders[0];

        // Update order based on response code
        if ( $codigo === '00' ) {
            // Payment approved
            if ( ! $order->is_paid() ) {
                $order->payment_complete( $control );
                $order->add_order_note( sprintf(
                    __( 'Pago confirmado por webhook. Control: %s, Autorización: %s', 'woocommerce-megasoft-gateway-v2' ),
                    $control,
                    $autorizacion
                ) );

                $this->logger->info( 'Pago confirmado por webhook', array(
                    'order_id' => $order->get_id(),
                    'control'  => $control,
                ) );
            }

            return array(
                'success' => true,
                'message' => 'Pago confirmado',
            );

        } else {
            // Payment failed or declined
            $order->update_status( 'failed', sprintf(
                __( 'Pago rechazado. Código: %s - %s', 'woocommerce-megasoft-gateway-v2' ),
                $codigo,
                $mensaje
            ) );

            $this->logger->warn( 'Pago rechazado en webhook', array(
                'order_id' => $order->get_id(),
                'control'  => $control,
                'codigo'   => $codigo,
            ) );

            return array(
                'success' => true,
                'message' => 'Pago rechazado procesado',
            );
        }
    }

    /**
     * Process refund notification
     *
     * @param SimpleXMLElement $xml XML object
     * @return array Result
     */
    private function process_refund_notification( $xml ) {
        $control = (string) ( $xml->control ?? '' );
        $codigo = (string) ( $xml->codigo ?? '' );
        $monto = (string) ( $xml->monto ?? '' );

        if ( empty( $control ) ) {
            throw new Exception( 'Número de control no proporcionado' );
        }

        // Find order
        $orders = wc_get_orders( array(
            'meta_key'   => '_megasoft_v2_control',
            'meta_value' => $control,
            'limit'      => 1,
        ) );

        if ( empty( $orders ) ) {
            return array(
                'success' => false,
                'message' => 'Orden no encontrada',
            );
        }

        $order = $orders[0];

        if ( $codigo === '00' ) {
            $order->add_order_note( sprintf(
                __( 'Reembolso confirmado por webhook. Control: %s, Monto: %s', 'woocommerce-megasoft-gateway-v2' ),
                $control,
                wc_price( floatval( $monto ) / 100 )
            ) );

            $this->logger->info( 'Reembolso confirmado por webhook', array(
                'order_id' => $order->get_id(),
                'control'  => $control,
            ) );
        }

        return array(
            'success' => true,
            'message' => 'Reembolso procesado',
        );
    }

    /**
     * Process chargeback notification
     *
     * @param SimpleXMLElement $xml XML object
     * @return array Result
     */
    private function process_chargeback_notification( $xml ) {
        $control = (string) ( $xml->control ?? '' );
        $monto = (string) ( $xml->monto ?? '' );
        $razon = (string) ( $xml->razon ?? 'No especificada' );

        if ( empty( $control ) ) {
            throw new Exception( 'Número de control no proporcionado' );
        }

        // Find order
        $orders = wc_get_orders( array(
            'meta_key'   => '_megasoft_v2_control',
            'meta_value' => $control,
            'limit'      => 1,
        ) );

        if ( empty( $orders ) ) {
            return array(
                'success' => false,
                'message' => 'Orden no encontrada',
            );
        }

        $order = $orders[0];

        // Update order status
        $order->update_status( 'refunded', sprintf(
            __( 'Contracargo reportado. Control: %s, Razón: %s', 'woocommerce-megasoft-gateway-v2' ),
            $control,
            $razon
        ) );

        $this->logger->warn( 'Contracargo reportado', array(
            'order_id' => $order->get_id(),
            'control'  => $control,
            'razon'    => $razon,
        ) );

        // Send admin notification
        $this->send_chargeback_notification( $order, $razon );

        return array(
            'success' => true,
            'message' => 'Contracargo procesado',
        );
    }

    /**
     * Save failed webhook for retry
     *
     * @param SimpleXMLElement $xml XML object
     * @param string $error_message Error message
     */
    private function save_failed_webhook( $xml, $error_message ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_failed_webhooks';

        $wpdb->insert(
            $table_name,
            array(
                'webhook_data' => $xml->asXML(),
                'error_message' => $error_message,
                'retry_count' => 0,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%d', '%s' )
        );

        $this->logger->info( 'Webhook guardado para reintento', array(
            'error' => $error_message,
        ) );
    }

    /**
     * Process pending webhooks (via cron)
     */
    public function process_pending_webhooks() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_failed_webhooks';

        // Get failed webhooks (max 3 retries)
        $webhooks = $wpdb->get_results(
            "SELECT * FROM $table_name
            WHERE retry_count < 3
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY created_at ASC
            LIMIT 10"
        );

        if ( empty( $webhooks ) ) {
            return;
        }

        $this->logger->info( 'Procesando webhooks pendientes', array(
            'count' => count( $webhooks ),
        ) );

        foreach ( $webhooks as $webhook ) {
            try {
                $xml = simplexml_load_string( $webhook->webhook_data );
                $result = $this->process_webhook( $xml );

                if ( $result['success'] ) {
                    // Delete if successful
                    $wpdb->delete( $table_name, array( 'id' => $webhook->id ), array( '%d' ) );

                    $this->logger->info( 'Webhook pendiente procesado exitosamente', array(
                        'webhook_id' => $webhook->id,
                    ) );
                } else {
                    // Increment retry count
                    $wpdb->update(
                        $table_name,
                        array( 'retry_count' => $webhook->retry_count + 1 ),
                        array( 'id' => $webhook->id ),
                        array( '%d' ),
                        array( '%d' )
                    );
                }
            } catch ( Exception $e ) {
                $this->logger->error( 'Error procesando webhook pendiente', array(
                    'webhook_id' => $webhook->id,
                    'error'      => $e->getMessage(),
                ) );

                // Increment retry count
                $wpdb->update(
                    $table_name,
                    array( 'retry_count' => $webhook->retry_count + 1 ),
                    array( 'id' => $webhook->id ),
                    array( '%d' ),
                    array( '%d' )
                );
            }
        }
    }

    /**
     * Send chargeback notification to admin
     *
     * @param WC_Order $order Order object
     * @param string $reason Chargeback reason
     */
    private function send_chargeback_notification( $order, $reason ) {
        $to = get_option( 'admin_email' );
        $subject = sprintf( __( '[ALERTA] Contracargo en orden #%s', 'woocommerce-megasoft-gateway-v2' ), $order->get_id() );

        $message = sprintf(
            __( "Se ha reportado un contracargo para la orden #%s.\n\nRazón: %s\nMonto: %s\nFecha: %s\n\nPor favor revisa la orden inmediatamente.", 'woocommerce-megasoft-gateway-v2' ),
            $order->get_id(),
            $reason,
            wc_price( $order->get_total() ),
            current_time( 'Y-m-d H:i:s' )
        );

        wp_mail( $to, $subject, $message );
    }

    /**
     * Verify IP address
     *
     * @return bool True if IP is allowed
     */
    private function verify_ip() {
        $client_ip = $this->get_client_ip();

        // Always allow in test mode
        $settings = get_option( 'woocommerce_megasoft_v2_settings', array() );
        if ( isset( $settings['testmode'] ) && $settings['testmode'] === 'yes' ) {
            return true;
        }

        // Check whitelist
        return in_array( $client_ip, $this->allowed_ips, true );
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private function get_client_ip() {
        $ip_keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );

        foreach ( $ip_keys as $key ) {
            if ( isset( $_SERVER[ $key ] ) && filter_var( $_SERVER[ $key ], FILTER_VALIDATE_IP ) ) {
                return sanitize_text_field( $_SERVER[ $key ] );
            }
        }

        return '0.0.0.0';
    }

    /**
     * Send HTTP response
     *
     * @param int $status_code HTTP status code
     * @param string $status_message Status message
     * @param array $data Response data
     */
    private function send_response( $status_code, $status_message, $data = array() ) {
        status_header( $status_code, $status_message );
        header( 'Content-Type: application/json' );

        echo json_encode( array_merge(
            array(
                'status'  => $status_code,
                'message' => $status_message,
            ),
            $data
        ) );

        exit;
    }

    /**
     * Get webhook URL
     *
     * @return string Webhook URL
     */
    public static function get_webhook_url() {
        return home_url( '/' . self::WEBHOOK_ENDPOINT . '/' );
    }
}
