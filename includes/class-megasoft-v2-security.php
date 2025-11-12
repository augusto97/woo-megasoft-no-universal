<?php
/**
 * MegaSoft Gateway v2 - Security Class
 *
 * Handles security validations, fraud detection, rate limiting,
 * and PCI DSS compliance checks
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MegaSoft_V2_Security {

    /**
     * @var MegaSoft_V2_Logger
     */
    private $logger;

    /**
     * Rate limiting settings (attempts per IP)
     */
    const MAX_ATTEMPTS_PER_HOUR = 10;
    const MAX_ATTEMPTS_PER_DAY = 50;
    const LOCKOUT_DURATION = 3600; // 1 hour in seconds

    /**
     * Fraud detection thresholds
     */
    const MAX_FAILED_TRANSACTIONS_ALERT = 3;
    const MAX_AMOUNT_PER_TRANSACTION = 999999999.99;
    const MIN_AMOUNT_PER_TRANSACTION = 0.01;

    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new MegaSoft_V2_Logger( true, 'warn' );
    }

    /**
     * Validate payment request data
     *
     * @param array $data Payment data
     * @return array Result with 'valid' and 'message'
     */
    public function validate_payment_data( $data ) {
        $errors = array();

        // Validate card number
        if ( empty( $data['card_number'] ) ) {
            $errors[] = __( 'Número de tarjeta es requerido', 'woocommerce-megasoft-gateway-v2' );
        } else {
            $card_number = $this->sanitize_card_number( $data['card_number'] );
            if ( ! $this->validate_card_number_format( $card_number ) ) {
                $errors[] = __( 'Formato de tarjeta inválido', 'woocommerce-megasoft-gateway-v2' );
            }
        }

        // Validate CVV
        if ( empty( $data['cvv'] ) ) {
            $errors[] = __( 'CVV es requerido', 'woocommerce-megasoft-gateway-v2' );
        } else {
            if ( ! $this->validate_cvv_format( $data['cvv'] ) ) {
                $errors[] = __( 'Formato de CVV inválido', 'woocommerce-megasoft-gateway-v2' );
            }
        }

        // Validate expiry date
        if ( empty( $data['expiry'] ) ) {
            $errors[] = __( 'Fecha de expiración es requerida', 'woocommerce-megasoft-gateway-v2' );
        } else {
            if ( ! $this->validate_expiry_date( $data['expiry'] ) ) {
                $errors[] = __( 'Fecha de expiración inválida o tarjeta expirada', 'woocommerce-megasoft-gateway-v2' );
            }
        }

        // Validate cardholder name
        if ( empty( $data['card_name'] ) ) {
            $errors[] = __( 'Nombre del titular es requerido', 'woocommerce-megasoft-gateway-v2' );
        } else {
            if ( ! $this->validate_cardholder_name( $data['card_name'] ) ) {
                $errors[] = __( 'Nombre del titular inválido', 'woocommerce-megasoft-gateway-v2' );
            }
        }

        // Validate document
        if ( empty( $data['doc_type'] ) || empty( $data['doc_number'] ) ) {
            $errors[] = __( 'Documento es requerido', 'woocommerce-megasoft-gateway-v2' );
        } else {
            if ( ! $this->validate_document( $data['doc_type'], $data['doc_number'] ) ) {
                $errors[] = __( 'Documento inválido', 'woocommerce-megasoft-gateway-v2' );
            }
        }

        // Validate amount
        if ( isset( $data['amount'] ) ) {
            if ( ! $this->validate_amount( $data['amount'] ) ) {
                $errors[] = __( 'Monto inválido', 'woocommerce-megasoft-gateway-v2' );
            }
        }

        if ( ! empty( $errors ) ) {
            return array(
                'valid'   => false,
                'message' => implode( '. ', $errors ),
            );
        }

        return array(
            'valid'   => true,
            'message' => 'Datos válidos',
        );
    }

    /**
     * Check rate limiting for IP address
     *
     * @param string $ip_address IP address
     * @param string $action Action type (payment, refund, etc)
     * @return bool True if allowed, false if rate limited
     */
    public function check_rate_limit( $ip_address, $action = 'payment' ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_security_log';
        $current_time = current_time( 'mysql' );

        // Check if IP is locked out
        if ( $this->is_ip_locked_out( $ip_address ) ) {
            $this->logger->warn( 'IP bloqueada por rate limiting', array(
                'ip'     => $ip_address,
                'action' => $action,
            ) );
            return false;
        }

        // Count attempts in last hour
        $one_hour_ago = date( 'Y-m-d H:i:s', strtotime( '-1 hour' ) );

        $attempts_last_hour = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name
            WHERE ip_address = %s
            AND action_type = %s
            AND created_at > %s",
            $ip_address,
            $action,
            $one_hour_ago
        ) );

        if ( $attempts_last_hour >= self::MAX_ATTEMPTS_PER_HOUR ) {
            $this->log_security_event( $ip_address, $action, 'rate_limit_exceeded_hour' );
            $this->lock_ip( $ip_address );
            return false;
        }

        // Count attempts in last day
        $one_day_ago = date( 'Y-m-d H:i:s', strtotime( '-1 day' ) );

        $attempts_last_day = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name
            WHERE ip_address = %s
            AND action_type = %s
            AND created_at > %s",
            $ip_address,
            $action,
            $one_day_ago
        ) );

        if ( $attempts_last_day >= self::MAX_ATTEMPTS_PER_DAY ) {
            $this->log_security_event( $ip_address, $action, 'rate_limit_exceeded_day' );
            $this->lock_ip( $ip_address );
            return false;
        }

        // Log this attempt
        $this->log_security_event( $ip_address, $action, 'attempt' );

        return true;
    }

    /**
     * Detect potential fraud indicators
     *
     * @param array $data Transaction data
     * @return array Fraud indicators
     */
    public function detect_fraud_indicators( $data ) {
        $indicators = array();
        $risk_score = 0;

        // Check for rapid successive transactions from same IP
        $ip_address = $this->get_client_ip();
        if ( $this->has_rapid_transactions( $ip_address ) ) {
            $indicators[] = 'rapid_transactions';
            $risk_score += 30;
        }

        // Check for unusual amount
        if ( isset( $data['amount'] ) ) {
            if ( $data['amount'] > 100000 ) { // High amount threshold
                $indicators[] = 'high_amount';
                $risk_score += 20;
            }

            if ( $data['amount'] < 1 ) { // Suspicious low amount
                $indicators[] = 'suspicious_low_amount';
                $risk_score += 10;
            }
        }

        // Check for mismatched country (if available)
        if ( isset( $data['billing_country'] ) && isset( $data['card_country'] ) ) {
            if ( $data['billing_country'] !== $data['card_country'] ) {
                $indicators[] = 'country_mismatch';
                $risk_score += 15;
            }
        }

        // Check for unusual time of day (midnight to 5am local time)
        $hour = (int) current_time( 'H' );
        if ( $hour >= 0 && $hour < 5 ) {
            $indicators[] = 'unusual_time';
            $risk_score += 5;
        }

        // Check for multiple failed attempts from same card
        if ( isset( $data['card_number'] ) ) {
            $card_hash = $this->hash_card_number( $data['card_number'] );
            if ( $this->has_multiple_failures( $card_hash ) ) {
                $indicators[] = 'multiple_card_failures';
                $risk_score += 25;
            }
        }

        // Log if risk score is high
        if ( $risk_score >= 50 ) {
            $this->logger->warn( 'Indicadores de fraude detectados', array(
                'risk_score' => $risk_score,
                'indicators' => $indicators,
                'ip'         => $ip_address,
            ) );
        }

        return array(
            'risk_score' => $risk_score,
            'indicators' => $indicators,
            'is_risky'   => $risk_score >= 70, // High risk threshold
        );
    }

    /**
     * Sanitize card number (remove spaces and non-digits)
     *
     * @param string $card_number Card number
     * @return string Sanitized card number
     */
    public function sanitize_card_number( $card_number ) {
        return preg_replace( '/\D/', '', $card_number );
    }

    /**
     * Validate card number format (length and Luhn)
     *
     * @param string $card_number Card number
     * @return bool Valid or not
     */
    private function validate_card_number_format( $card_number ) {
        // Check length (13-19 digits)
        $length = strlen( $card_number );
        if ( $length < 13 || $length > 19 ) {
            return false;
        }

        // Validate Luhn algorithm
        return $this->validate_luhn( $card_number );
    }

    /**
     * Validate using Luhn algorithm
     *
     * @param string $card_number Card number
     * @return bool Valid or not
     */
    private function validate_luhn( $card_number ) {
        $sum = 0;
        $num_digits = strlen( $card_number );
        $parity = $num_digits % 2;

        for ( $i = 0; $i < $num_digits; $i++ ) {
            $digit = (int) $card_number[ $i ];

            if ( $i % 2 === $parity ) {
                $digit *= 2;
            }

            if ( $digit > 9 ) {
                $digit -= 9;
            }

            $sum += $digit;
        }

        return ( $sum % 10 ) === 0;
    }

    /**
     * Validate CVV format
     *
     * @param string $cvv CVV
     * @return bool Valid or not
     */
    private function validate_cvv_format( $cvv ) {
        // CVV should be 3 or 4 digits
        return preg_match( '/^[0-9]{3,4}$/', $cvv );
    }

    /**
     * Validate expiry date
     *
     * @param string $expiry Expiry date (MM/YY or MMYY)
     * @return bool Valid or not
     */
    private function validate_expiry_date( $expiry ) {
        // Remove any separators
        $expiry = str_replace( array( '/', '-', ' ' ), '', $expiry );

        // Should be 4 digits (MMYY)
        if ( ! preg_match( '/^[0-9]{4}$/', $expiry ) ) {
            return false;
        }

        $month = (int) substr( $expiry, 0, 2 );
        $year = (int) substr( $expiry, 2, 2 );

        // Validate month
        if ( $month < 1 || $month > 12 ) {
            return false;
        }

        // Convert to 4-digit year
        $year += 2000;

        // Check if expired
        $current_year = (int) date( 'Y' );
        $current_month = (int) date( 'm' );

        if ( $year < $current_year ) {
            return false;
        }

        if ( $year === $current_year && $month < $current_month ) {
            return false;
        }

        return true;
    }

    /**
     * Validate cardholder name
     *
     * @param string $name Cardholder name
     * @return bool Valid or not
     */
    private function validate_cardholder_name( $name ) {
        $name = trim( $name );

        // Minimum 3 characters
        if ( strlen( $name ) < 3 ) {
            return false;
        }

        // Only letters, spaces, hyphens, apostrophes
        if ( ! preg_match( "/^[a-zA-ZÀ-ÿ\s\-'\.]+$/u", $name ) ) {
            return false;
        }

        return true;
    }

    /**
     * Validate document
     *
     * @param string $type Document type (V, E, J, G, P, C)
     * @param string $number Document number
     * @return bool Valid or not
     */
    private function validate_document( $type, $number ) {
        // Valid document types
        $valid_types = array( 'V', 'E', 'J', 'G', 'P', 'C' );
        if ( ! in_array( strtoupper( $type ), $valid_types, true ) ) {
            return false;
        }

        // Document number should be numeric and at least 5 digits
        $number = preg_replace( '/\D/', '', $number );
        if ( strlen( $number ) < 5 || strlen( $number ) > 20 ) {
            return false;
        }

        return true;
    }

    /**
     * Validate amount
     *
     * @param float $amount Amount
     * @return bool Valid or not
     */
    private function validate_amount( $amount ) {
        $amount = floatval( $amount );

        if ( $amount < self::MIN_AMOUNT_PER_TRANSACTION ) {
            return false;
        }

        if ( $amount > self::MAX_AMOUNT_PER_TRANSACTION ) {
            return false;
        }

        return true;
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    public function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // CloudFlare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        );

        foreach ( $ip_keys as $key ) {
            if ( isset( $_SERVER[ $key ] ) && filter_var( $_SERVER[ $key ], FILTER_VALIDATE_IP ) ) {
                return sanitize_text_field( $_SERVER[ $key ] );
            }
        }

        return '0.0.0.0';
    }

    /**
     * Check if IP is locked out
     *
     * @param string $ip_address IP address
     * @return bool True if locked out
     */
    private function is_ip_locked_out( $ip_address ) {
        $lockout = get_transient( 'megasoft_v2_lockout_' . md5( $ip_address ) );
        return ! empty( $lockout );
    }

    /**
     * Lock IP address
     *
     * @param string $ip_address IP address
     */
    private function lock_ip( $ip_address ) {
        set_transient( 'megasoft_v2_lockout_' . md5( $ip_address ), true, self::LOCKOUT_DURATION );

        $this->logger->warn( 'IP bloqueada', array(
            'ip'       => $ip_address,
            'duration' => self::LOCKOUT_DURATION,
        ) );
    }

    /**
     * Log security event
     *
     * @param string $ip_address IP address
     * @param string $action_type Action type
     * @param string $event_type Event type
     * @param array $metadata Additional metadata
     */
    private function log_security_event( $ip_address, $action_type, $event_type, $metadata = array() ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_security_log';

        $wpdb->insert(
            $table_name,
            array(
                'ip_address'  => $ip_address,
                'user_id'     => get_current_user_id(),
                'action_type' => $action_type,
                'event_type'  => $event_type,
                'metadata'    => json_encode( $metadata ),
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%s', '%d', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Check for rapid successive transactions
     *
     * @param string $ip_address IP address
     * @return bool True if rapid transactions detected
     */
    private function has_rapid_transactions( $ip_address ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_security_log';
        $five_minutes_ago = date( 'Y-m-d H:i:s', strtotime( '-5 minutes' ) );

        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name
            WHERE ip_address = %s
            AND action_type = 'payment'
            AND created_at > %s",
            $ip_address,
            $five_minutes_ago
        ) );

        return $count >= 5; // More than 5 transactions in 5 minutes
    }

    /**
     * Hash card number for tracking (PCI compliant - one-way hash)
     *
     * @param string $card_number Card number
     * @return string Hash
     */
    private function hash_card_number( $card_number ) {
        $sanitized = $this->sanitize_card_number( $card_number );
        return hash( 'sha256', $sanitized . wp_salt() );
    }

    /**
     * Check for multiple failures with same card
     *
     * @param string $card_hash Card hash
     * @return bool True if multiple failures detected
     */
    private function has_multiple_failures( $card_hash ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_transactions';
        $one_day_ago = date( 'Y-m-d H:i:s', strtotime( '-1 day' ) );

        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name
            WHERE card_hash = %s
            AND status IN ('failed', 'declined')
            AND created_at > %s",
            $card_hash,
            $one_day_ago
        ) );

        return $count >= self::MAX_FAILED_TRANSACTIONS_ALERT;
    }

    /**
     * Verify SSL connection
     *
     * @return bool True if SSL is active
     */
    public function verify_ssl() {
        return is_ssl();
    }

    /**
     * Generate secure token for transaction
     *
     * @param string $order_id Order ID
     * @return string Token
     */
    public function generate_transaction_token( $order_id ) {
        return wp_hash( $order_id . time() . wp_generate_password( 32, true, true ) );
    }

    /**
     * Verify transaction token
     *
     * @param string $token Token
     * @param int $order_id Order ID
     * @return bool Valid or not
     */
    public function verify_transaction_token( $token, $order_id ) {
        $stored_token = get_post_meta( $order_id, '_megasoft_v2_transaction_token', true );
        return hash_equals( $stored_token, $token );
    }

    /**
     * Sanitize all payment data (PCI compliance)
     * CRITICAL: Never log or store PAN, CVV, or full expiry
     *
     * @param array $data Payment data
     * @return array Sanitized data (safe for logging)
     */
    public function sanitize_for_logging( $data ) {
        $sanitized = $data;

        // Remove sensitive card data
        $sensitive_keys = array( 'card_number', 'cvv', 'cvv2', 'pan', 'expiry', 'expdate' );

        foreach ( $sensitive_keys as $key ) {
            if ( isset( $sanitized[ $key ] ) ) {
                unset( $sanitized[ $key ] );
            }
        }

        // Only keep last 4 digits of card if present
        if ( isset( $data['card_number'] ) ) {
            $card_number = $this->sanitize_card_number( $data['card_number'] );
            $sanitized['card_last_four'] = substr( $card_number, -4 );
        }

        return $sanitized;
    }

    /**
     * Check if request is from valid user agent (block bots)
     *
     * @return bool True if valid
     */
    public function is_valid_user_agent() {
        if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
            return false;
        }

        $user_agent = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] );

        // Block known bots and scrapers
        $blocked_patterns = array(
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i',
        );

        foreach ( $blocked_patterns as $pattern ) {
            if ( preg_match( $pattern, $user_agent ) ) {
                $this->logger->warn( 'User agent bloqueado', array(
                    'user_agent' => $user_agent,
                    'ip'         => $this->get_client_ip(),
                ) );
                return false;
            }
        }

        return true;
    }

    /**
     * Validate SSL certificate (for production)
     *
     * @return array Validation result
     */
    public function validate_ssl_certificate() {
        if ( ! is_ssl() ) {
            return array(
                'valid'   => false,
                'message' => __( 'SSL no está activo', 'woocommerce-megasoft-gateway-v2' ),
            );
        }

        // Additional SSL checks could be added here
        // (certificate expiry, issuer validation, etc.)

        return array(
            'valid'   => true,
            'message' => __( 'SSL activo y válido', 'woocommerce-megasoft-gateway-v2' ),
        );
    }

    /**
     * Clean old security logs (run via cron)
     *
     * @param int $days_to_keep Number of days to keep logs
     */
    public static function cleanup_old_logs( $days_to_keep = 90 ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_security_log';
        $cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$days_to_keep} days" ) );

        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            $cutoff_date
        ) );

        if ( $deleted ) {
            $logger = new MegaSoft_V2_Logger( true, 'info' );
            $logger->info( 'Logs de seguridad antiguos eliminados', array(
                'deleted_count' => $deleted,
                'cutoff_date'   => $cutoff_date,
            ) );
        }
    }
}
