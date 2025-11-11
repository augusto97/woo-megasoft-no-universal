<?php
/**
 * MegaSoft Security Patch
 * Parches de seguridad críticos para XXE y timing attacks
 * 
 * @package WooCommerce_MegaSoft_Gateway
 * @version 3.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Aplicar parches de seguridad a la clase del gateway
 */
add_action( 'woocommerce_loaded', 'megasoft_apply_security_patches', 12 );

function megasoft_apply_security_patches() {
    if ( ! class_exists( 'WC_Gateway_MegaSoft_Universal' ) ) {
        return;
    }
    
    // Registrar filtros para sobrescribir funciones vulnerables
    add_filter( 'megasoft_parse_xml_response', 'megasoft_secure_xml_parser', 10, 2 );
    add_filter( 'megasoft_validate_control_match', 'megasoft_secure_control_validation', 10, 2 );
}

/**
 * Parser XML seguro con protección XXE
 * 
 * @param string $xml_string XML a parsear
 * @param string $control_number Número de control (para logging)
 * @return array|false Resultado parseado o false en error
 */
function megasoft_secure_xml_parser( $xml_string, $control_number = '' ) {
    // Protección XXE: Desactivar carga de entidades externas
    $previous_value = libxml_disable_entity_loader( true );
    libxml_use_internal_errors( true );
    
    try {
        // Parsear XML con flags de seguridad
        $xml = simplexml_load_string(
            $xml_string,
            'SimpleXMLElement',
            LIBXML_NOENT | LIBXML_NOCDATA | LIBXML_NONET
        );
        
        if ( $xml === false ) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            
            // Log de error
            if ( class_exists( 'MegaSoft_Logger' ) ) {
                $logger = new MegaSoft_Logger( true, 'error' );
                $logger->error( "Error parsing XML (secure)", array(
                    'control_number' => $control_number,
                    'errors' => array_map( function( $error ) {
                        return $error->message;
                    }, $errors )
                ) );
            }
            
            return false;
        }
        
        $codigo = (string) $xml->codigo;
        $approved = $codigo === '00';
        
        $result = array(
            'approved'       => $approved,
            'code'           => $codigo,
            'message'        => (string) $xml->descripcion,
            'auth_id'        => (string) $xml->authid,
            'reference'      => (string) $xml->referencia,
            'payment_method' => (string) $xml->medio,
            'amount'         => (string) $xml->monto,
            'terminal'       => (string) $xml->terminal,
            'lote'           => (string) $xml->lote,
            'sequence'       => (string) $xml->seqnum,
            'voucher'        => megasoft_secure_format_voucher( $xml ),
            'raw_data'       => json_decode( json_encode( $xml ), true ),
            'metadata'       => array(
                'auth_id'        => (string) $xml->authid,
                'reference'      => (string) $xml->referencia,
                'payment_method' => (string) $xml->medio,
                'terminal'       => (string) $xml->terminal,
                'lote'           => (string) $xml->lote,
                'sequence'       => (string) $xml->seqnum
            )
        );
        
        return $result;
        
    } catch ( Exception $e ) {
        if ( class_exists( 'MegaSoft_Logger' ) ) {
            $logger = new MegaSoft_Logger( true, 'error' );
            $logger->error( "Exception parsing XML: " . $e->getMessage(), array(
                'control_number' => $control_number
            ) );
        }
        
        return false;
        
    } finally {
        // Restaurar configuración original
        libxml_disable_entity_loader( $previous_value );
    }
}

/**
 * Formatear voucher de forma segura
 * 
 * @param SimpleXMLElement $xml XML parseado
 * @return string HTML del voucher
 */
function megasoft_secure_format_voucher( $xml ) {
    $voucher_lines = array();
    
    // Verificar si hay voucher en el XML
    if ( isset( $xml->voucher ) && isset( $xml->voucher->linea ) ) {
        foreach ( $xml->voucher->linea as $linea ) {
            $line = (string) $linea;
            // Sanitizar cada línea del voucher
            $voucher_lines[] = wp_kses( $line, array(
                'br' => array(),
                'strong' => array(),
                'b' => array(),
                'em' => array(),
                'i' => array()
            ) );
        }
    }
    
    // Si no hay voucher, generar uno básico
    if ( empty( $voucher_lines ) ) {
        $codigo = (string) $xml->codigo;
        $approved = $codigo === '00';
        
        $voucher_lines = array(
            $approved ? 'TRANSACCIÓN APROBADA' : 'TRANSACCIÓN RECHAZADA',
            '',
            'MEGA SOFT COMPUTACIÓN',
            isset( $xml->terminal ) ? 'TERMINAL: ' . esc_html( (string) $xml->terminal ) : '',
            isset( $xml->factura ) ? 'FACTURA: ' . esc_html( (string) $xml->factura ) : '',
            isset( $xml->referencia ) ? 'REFERENCIA: ' . esc_html( (string) $xml->referencia ) : '',
            isset( $xml->authid ) && !empty( (string) $xml->authid ) ? 'AUTORIZACIÓN: ' . esc_html( (string) $xml->authid ) : '',
            'FECHA: ' . date( 'd/m/Y H:i:s' ),
            '',
            isset( $xml->monto ) ? 'MONTO BS: ' . esc_html( (string) $xml->monto ) : '',
            '',
            $approved ? 'TRANSACCIÓN EXITOSA' : 'CÓDIGO: ' . esc_html( $codigo ),
            $approved ? '' : esc_html( strtoupper( (string) $xml->descripcion ) ),
            '',
        );
        
        // Remover líneas vacías
        $voucher_lines = array_filter( $voucher_lines, function( $line ) {
            return $line !== null && $line !== '';
        });
    }
    
    return megasoft_render_secure_voucher( $voucher_lines, (string) $xml->codigo === '00', $xml );
}

/**
 * Renderizar voucher con HTML seguro
 * 
 * @param array $voucher_lines Líneas del voucher
 * @param bool $approved Si la transacción fue aprobada
 * @param SimpleXMLElement $xml Datos XML
 * @return string HTML del voucher
 */
function megasoft_render_secure_voucher( $voucher_lines, $approved, $xml ) {
    $status_class = $approved ? 'approved' : 'failed';
    $status_icon = $approved ? '✅' : '❌';
    $status_text = $approved ? 'APROBADA' : 'RECHAZADA';
    
    $codigo = esc_html( (string) $xml->codigo );
    $descripcion = esc_html( (string) $xml->descripcion );
    
    ob_start();
    ?>
    <div class="megasoft-voucher-receipt <?php echo esc_attr( $status_class ); ?>">
        <div class="voucher-header">
            <div class="voucher-logo"><?php echo $status_icon; ?></div>
            <h3><?php echo esc_html__( 'COMPROBANTE DE TRANSACCIÓN', 'woocommerce-megasoft-gateway-universal' ); ?></h3>
            <div class="voucher-date"><?php echo esc_html( date( 'd/m/Y H:i:s' ) ); ?></div>
            <div class="voucher-status <?php echo esc_attr( $status_class ); ?>">
                <?php echo esc_html( $status_text ); ?>
                <?php if ( ! $approved ) : ?>
                    - <?php echo esc_html( $descripcion ); ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="voucher-content">
            <?php foreach ( $voucher_lines as $line ) : ?>
                <div class="voucher-line"><?php echo wp_kses_post( $line ); ?></div>
            <?php endforeach; ?>
        </div>
        
        <div class="voucher-footer">
            <div class="security-note">
                🔒 <?php echo esc_html__( 'Transacción procesada de forma segura', 'woocommerce-megasoft-gateway-universal' ); ?>
            </div>
            <div class="transaction-id">
                <?php echo esc_html__( 'ID de Control:', 'woocommerce-megasoft-gateway-universal' ); ?> 
                <code><?php echo esc_html( (string) $xml->control ); ?></code>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Validación segura de números de control usando comparación constante en tiempo
 * 
 * @param string $saved_control Control guardado en la orden
 * @param string $received_control Control recibido del retorno
 * @return bool True si coinciden
 */
function megasoft_secure_control_validation( $saved_control, $received_control ) {
    // Convertir a string para asegurar comparación correcta
    $saved_control = (string) $saved_control;
    $received_control = (string) $received_control;
    
    // Validar que no estén vacíos
    if ( empty( $saved_control ) || empty( $received_control ) ) {
        // Log de intento con valores vacíos
        if ( function_exists( 'megasoft_log_security_event' ) ) {
            megasoft_log_security_event( 'control_validation_empty', array(
                'saved_empty' => empty( $saved_control ),
                'received_empty' => empty( $received_control )
            ) );
        }
        return false;
    }
    
    // Validar formato antes de comparar
    if ( ! is_numeric( $saved_control ) || ! is_numeric( $received_control ) ) {
        if ( function_exists( 'megasoft_log_security_event' ) ) {
            megasoft_log_security_event( 'control_validation_invalid_format', array(
                'saved_is_numeric' => is_numeric( $saved_control ),
                'received_is_numeric' => is_numeric( $received_control )
            ) );
        }
        return false;
    }
    
    // Comparación constante en tiempo para prevenir timing attacks
    $valid = hash_equals( $saved_control, $received_control );
    
    // Log de validación fallida
    if ( ! $valid && function_exists( 'megasoft_log_security_event' ) ) {
        megasoft_log_security_event( 'control_validation_failed', array(
            'saved_length' => strlen( $saved_control ),
            'received_length' => strlen( $received_control ),
            'ip' => function_exists( 'megasoft_get_client_ip' ) ? megasoft_get_client_ip() : 'unknown'
        ) );
    }
    
    return $valid;
}

/**
 * Hook para reemplazar parse_transaction_response en el gateway
 */
add_filter( 'woocommerce_loaded', function() {
    if ( ! class_exists( 'WC_Gateway_MegaSoft_Universal' ) ) {
        return;
    }
    
    // Interceptar el método parse_transaction_response
    add_filter( 'megasoft_before_parse_transaction', function( $xml_string, $control_number ) {
        return megasoft_secure_xml_parser( $xml_string, $control_number );
    }, 10, 2 );
    
    // Interceptar la validación de números de control
    add_filter( 'megasoft_before_control_validation', function( $saved, $received ) {
        return megasoft_secure_control_validation( $saved, $received );
    }, 10, 2 );
}, 13 );