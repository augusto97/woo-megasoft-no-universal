<?php
/**
 * MegaSoft Transaction Saver - Guardado simplificado de transacciones
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MegaSoft_V2_Transaction_Saver {

    /**
     * Save alternative payment transaction to database (P2C, C2P, Crédito Inmediato)
     *
     * @param WC_Order $order Order object
     * @param array $response API response
     * @param string $payment_type Payment type (P2C, C2P, CREDITOINMEDIATO)
     * @param array $payment_data Additional payment data
     * @return bool|int Insert ID or false on failure
     */
    public static function save_alternative_payment( $order, $response, $payment_type, $payment_data = array() ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_transactions';

        // Verify table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

        if ( ! $table_exists ) {
            error_log( "[MEGASOFT V2] ERROR: Table $table_name does not exist!" );
            return false;
        }

        // Extract data
        $order_id = $order->get_id();
        $control_number = self::extract_value( $response, 'control' );
        $authorization_code = self::extract_value( $response, 'authid' );
        $amount = $order->get_total();
        $currency = $order->get_currency();
        $response_code = $response['codigo'] ?? '';
        $response_message = $response['mensaje'] ?? $response['descripcion'] ?? '';
        $transaction_date = current_time( 'mysql' );

        // Determine status based on codigo
        $status = ( $response_code === '00' ) ? 'approved' : 'declined';

        $raw_response = json_encode( $response, JSON_UNESCAPED_UNICODE );

        // Payment method specific info
        $card_last_four = '';
        $card_type = $payment_type; // Use payment type as card_type for alternative methods

        // Insert transaction
        $result = $wpdb->insert(
            $table_name,
            array(
                'order_id'             => $order_id,
                'control_number'       => $control_number,
                'authorization_code'   => $authorization_code,
                'transaction_type'     => $payment_type,
                'amount'               => $amount,
                'currency'             => $currency,
                'card_last_four'       => $card_last_four,
                'card_type'            => $card_type,
                'response_code'        => $response_code,
                'response_message'     => $response_message,
                'transaction_date'     => $transaction_date,
                'status'               => $status,
                'raw_response'         => $raw_response,
                'created_at'           => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( $result === false ) {
            error_log( "[MEGASOFT V2] ERROR saving alternative payment transaction: " . $wpdb->last_error );
            error_log( "[MEGASOFT V2] Order ID: $order_id, Control: $control_number, Type: $payment_type" );
            return false;
        }

        $insert_id = $wpdb->insert_id;

        // Save to order meta
        self::save_alternative_payment_meta( $order, $response, $payment_type, $payment_data );

        error_log( "[MEGASOFT V2] Alternative payment transaction saved. ID: $insert_id, Order: $order_id, Type: $payment_type" );

        return $insert_id;
    }

    /**
     * Save alternative payment data to order meta
     */
    private static function save_alternative_payment_meta( $order, $response, $payment_type, $payment_data ) {
        $order->update_meta_data( '_megasoft_v2_payment_type', $payment_type );
        $order->update_meta_data( '_megasoft_v2_control', self::extract_value( $response, 'control' ) );
        $order->update_meta_data( '_megasoft_v2_authorization', self::extract_value( $response, 'authid' ) );
        $order->update_meta_data( '_megasoft_v2_terminal', self::extract_value( $response, 'terminal' ) );
        $order->update_meta_data( '_megasoft_v2_seqnum', self::extract_value( $response, 'seqnum' ) );
        $order->update_meta_data( '_megasoft_v2_referencia', self::extract_value( $response, 'referencia' ) );
        $order->update_meta_data( '_megasoft_v2_response_code', $response['codigo'] ?? '' );

        // Save payment method specific data
        if ( ! empty( $payment_data ) ) {
            foreach ( $payment_data as $key => $value ) {
                $order->update_meta_data( '_megasoft_v2_' . $key, $value );
            }
        }

        $order->save();
    }

    /**
     * Extract value from array, handling both strings and arrays
     */
    private static function extract_value( $data, $key, $default = '' ) {
        if ( ! isset( $data[ $key ] ) ) {
            return $default;
        }

        $value = $data[ $key ];

        // If it's already a string, return it
        if ( is_string( $value ) ) {
            return $value;
        }

        // If it's an array, try to extract meaningful value
        if ( is_array( $value ) ) {
            if ( isset( $value[0] ) && is_string( $value[0] ) ) {
                return $value[0];
            }
            return implode( ' ', array_filter( $value, 'is_scalar' ) );
        }

        return (string) $value;
    }

    /**
     * Save transaction to database
     *
     * @param WC_Order $order Order object
     * @param array $response API response
     * @param array $card_data Card data (sanitized)
     * @return bool|int Insert ID or false on failure
     */
    public static function save( $order, $response, $card_data ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_transactions';

        // Verify table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

        if ( ! $table_exists ) {
            // Log error
            error_log( "[MEGASOFT V2] ERROR: Table $table_name does not exist!" );
            return false;
        }

        // Extract data
        $order_id = $order->get_id();
        $control_number = $response['control'] ?? '';
        $authorization_code = $response['autorizacion'] ?? '';
        $transaction_type = sanitize_text_field( $_POST['megasoft_v2_card_type'] ?? 'CREDITO' );
        $amount = $order->get_total();
        $currency = $order->get_currency();
        $card_last_four = substr( $card_data['number'], -4 );
        $card_type = self::detect_card_brand( $card_data['number'] );
        $response_code = $response['codigo'] ?? '';
        $response_message = $response['mensaje'] ?? '';
        $transaction_date = $response['fecha'] ?? current_time( 'mysql' );
        $status = 'approved';
        $raw_response = json_encode( $response, JSON_UNESCAPED_UNICODE );

        // Insert transaction
        $result = $wpdb->insert(
            $table_name,
            array(
                'order_id'             => $order_id,
                'control_number'       => $control_number,
                'authorization_code'   => $authorization_code,
                'transaction_type'     => $transaction_type,
                'amount'               => $amount,
                'currency'             => $currency,
                'card_last_four'       => $card_last_four,
                'card_type'            => $card_type,
                'response_code'        => $response_code,
                'response_message'     => $response_message,
                'transaction_date'     => $transaction_date,
                'status'               => $status,
                'raw_response'         => $raw_response,
                'created_at'           => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( $result === false ) {
            // Log error
            error_log( "[MEGASOFT V2] ERROR saving transaction to DB: " . $wpdb->last_error );
            error_log( "[MEGASOFT V2] Order ID: $order_id, Control: $control_number" );
            return false;
        }

        $insert_id = $wpdb->insert_id;

        // Also save to order meta
        self::save_to_order_meta( $order, $response, $card_last_four, $card_type );

        // Log success
        error_log( "[MEGASOFT V2] Transaction saved successfully. Insert ID: $insert_id, Order: $order_id, Control: $control_number" );

        return $insert_id;
    }

    /**
     * Save transaction data to order meta
     */
    private static function save_to_order_meta( $order, $response, $card_last_four, $card_type ) {
        $order->update_meta_data( '_megasoft_v2_control', $response['control'] ?? '' );
        $order->update_meta_data( '_megasoft_v2_authorization', $response['autorizacion'] ?? '' );
        $order->update_meta_data( '_megasoft_v2_card_last_four', $card_last_four );
        $order->update_meta_data( '_megasoft_v2_card_type', $card_type );
        $order->update_meta_data( '_megasoft_v2_terminal', $response['terminal'] ?? '' );
        $order->update_meta_data( '_megasoft_v2_seqnum', $response['seqnum'] ?? '' );
        $order->update_meta_data( '_megasoft_v2_referencia', $response['referencia'] ?? '' );
        $order->update_meta_data( '_megasoft_v2_response_code', $response['codigo'] ?? '' );
        $order->update_meta_data( '_megasoft_v2_transaction_date', $response['fecha'] ?? current_time( 'mysql' ) );
        $order->save();
    }

    /**
     * Detect card brand from card number
     */
    private static function detect_card_brand( $number ) {
        $number = preg_replace( '/\s+/', '', $number );

        $patterns = array(
            'visa'       => '/^4/',
            'mastercard' => '/^(5[1-5]|2[2-7])/',
            'amex'       => '/^3[47]/',
            'discover'   => '/^6(?:011|5)/',
            'diners'     => '/^3(?:0[0-5]|[68])/',
            'jcb'        => '/^35/',
        );

        foreach ( $patterns as $brand => $pattern ) {
            if ( preg_match( $pattern, $number ) ) {
                return $brand;
            }
        }

        return 'unknown';
    }

    /**
     * Get transaction by order ID
     */
    public static function get_by_order_id( $order_id ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_transactions';

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE order_id = %d ORDER BY created_at DESC LIMIT 1",
            $order_id
        ) );
    }

    /**
     * Get transaction by control number
     */
    public static function get_by_control( $control_number ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_transactions';

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE control_number = %s LIMIT 1",
            $control_number
        ) );
    }

    /**
     * Get recent transactions
     */
    public static function get_recent( $limit = 10 ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_transactions';

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d",
            $limit
        ) );
    }

    /**
     * Get transaction stats
     */
    public static function get_stats() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_transactions';

        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

        if ( ! $table_exists ) {
            return array(
                'total' => 0,
                'approved' => 0,
                'failed' => 0,
                'total_amount' => 0,
            );
        }

        return array(
            'total' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ),
            'approved' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'approved'" ),
            'failed' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status IN ('failed', 'declined')" ),
            'total_amount' => (float) $wpdb->get_var( "SELECT SUM(amount) FROM $table_name WHERE status = 'approved'" ) ?? 0,
        );
    }
}
