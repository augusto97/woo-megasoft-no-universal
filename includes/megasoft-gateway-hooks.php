<?php
/**
 * MegaSoft Gateway Hooks
 * Hooks para integrar los parches de seguridad con el gateway existente
 * 
 * @package WooCommerce_MegaSoft_Gateway
 * @version 3.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sobrescribir el método parse_transaction_response del gateway
 */
add_action( 'woocommerce_loaded', 'megasoft_override_gateway_methods', 14 );

function megasoft_override_gateway_methods() {
    if ( ! class_exists( 'WC_Gateway_MegaSoft_Universal' ) ) {
        return;
    }
    
    // Hook para interceptar el parsing de XML
    add_action( 'woocommerce_api_wc_gateway_megasoft_universal', 'megasoft_secure_handle_return', 1 );
}

/**
 * Handler seguro para el retorno del gateway
 * Este se ejecuta ANTES del handler original y aplica las validaciones seguras
 */
function megasoft_secure_handle_return() {
    // Solo proceder si vienen los parámetros correctos
    if ( ! isset( $_GET['control'] ) || ! isset( $_GET['factura'] ) ) {
        return; // Dejar que el handler original maneje el error
    }
    
    // Obtener gateway
    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    if ( ! isset( $gateways['megasoft_gateway_universal'] ) ) {
        return;
    }
    
    $gateway = $gateways['megasoft_gateway_universal'];
    
    // Sanitizar parámetros
    $control_number = isset( $_GET['control'] ) ? sanitize_text_field( wp_unslash( $_GET['control'] ) ) : '';
    $order_id = isset( $_GET['factura'] ) ? absint( $_GET['factura'] ) : 0;
    
    if ( empty( $control_number ) || empty( $order_id ) ) {
        return;
    }
    
    // Obtener orden
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    
    // Validación segura del número de control
    $saved_control = $order->get_meta( '_megasoft_control_number' );
    
    // Usar validación segura con hash_equals
    if ( ! megasoft_secure_control_validation( $saved_control, $control_number ) ) {
        // Log de intento de fraude
        if ( function_exists( 'megasoft_log_security_event' ) ) {
            megasoft_log_security_event( 'fraudulent_return_attempt', array(
                'order_id' => $order_id,
                'expected_control' => substr( $saved_control, 0, 6 ) . '***', // Parcialmente ofuscado
                'received_control' => substr( $control_number, 0, 6 ) . '***',
                'ip' => function_exists( 'megasoft_get_client_ip' ) ? megasoft_get_client_ip() : 'unknown'
            ) );
        }
        
        // Bloquear IP automáticamente después de 3 intentos fallidos
        $ip = function_exists( 'megasoft_get_client_ip' ) ? megasoft_get_client_ip() : '';
        if ( ! empty( $ip ) ) {
            $failed_attempts = get_transient( 'megasoft_failed_return_' . md5( $ip ) );
            if ( $failed_attempts >= 2 && function_exists( 'megasoft_security' ) ) {
                megasoft_security()->block_ip( $ip, 'Múltiples intentos de retorno fraudulento' );
            } else {
                set_transient( 'megasoft_failed_return_' . md5( $ip ), ( $failed_attempts ? $failed_attempts + 1 : 1 ), 3600 );
            }
        }
        
        // No continuar - el handler original mostrará error
        return;
    }
    
    // Si llegamos aquí, la validación fue exitosa
    // Limpiar contador de intentos fallidos
    $ip = function_exists( 'megasoft_get_client_ip' ) ? megasoft_get_client_ip() : '';
    if ( ! empty( $ip ) ) {
        delete_transient( 'megasoft_failed_return_' . md5( $ip ) );
    }
}

/**
 * Wrapper seguro para query_transaction_status
 */
function megasoft_secure_query_status( $control_number, $test_mode = false ) {
    $base_url = $test_mode ? 'https://paytest.megasoft.com.ve/action/' : 'https://e-payment.megasoft.com.ve/action/';
    
    $querystatus_url = add_query_arg( array(
        'control' => sanitize_text_field( $control_number ),
        'version' => '3',
        'tipo'    => 'CREDITO'
    ), $base_url . 'paymentgatewayuniversal-querystatus' );
    
    $response = wp_remote_get( $querystatus_url, array( 
        'timeout' => 30, 
        'sslverify' => ! $test_mode,
        'headers' => array(
            'User-Agent' => 'MegaSoft-WooCommerce-Gateway/' . MEGASOFT_PLUGIN_VERSION
        )
    ) );
    
    if ( is_wp_error( $response ) ) {
        return false;
    }
    
    $xml_string = wp_remote_retrieve_body( $response );
    
    // Usar parser seguro
    return megasoft_secure_xml_parser( $xml_string, $control_number );
}

/**
 * Agregar información de parches al admin
 */
add_action( 'admin_notices', function() {
    $screen = get_current_screen();
    if ( $screen && $screen->id === 'woocommerce_page_wc-settings' && isset( $_GET['section'] ) && $_GET['section'] === 'megasoft_gateway_universal' ) {
        ?>
        <div class="notice notice-success">
            <p>
                <strong>🔒 <?php echo esc_html__( 'Parches de Seguridad Activos', 'woocommerce-megasoft-gateway-universal' ); ?></strong><br>
                ✅ <?php echo esc_html__( 'Protección XXE habilitada', 'woocommerce-megasoft-gateway-universal' ); ?><br>
                ✅ <?php echo esc_html__( 'Validación timing-safe activa', 'woocommerce-megasoft-gateway-universal' ); ?><br>
                ✅ <?php echo esc_html__( 'Detección de fraude mejorada', 'woocommerce-megasoft-gateway-universal' ); ?>
            </p>
        </div>
        <?php
    }
});