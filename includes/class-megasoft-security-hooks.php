<?php
/**
 * MegaSoft Security Hooks
 * Integra la seguridad con el plugin existente sin modificar el código original
 * 
 * @package WooCommerce_MegaSoft_Gateway
 * @version 3.0.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MegaSoft_Security_Hooks {
    
    /**
     * Instancia de seguridad
     */
    private $security;
    
    /**
     * Logger
     */
    private $logger;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->security = MegaSoft_Security::get_instance();
        
        if ( class_exists( 'MegaSoft_Logger' ) ) {
            $this->logger = new MegaSoft_Logger( true, 'info' );
        }
        
        $this->init_hooks();
    }
    
    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Hooks de sanitización en checkout
        add_filter( 'woocommerce_checkout_posted_data', array( $this, 'sanitize_checkout_data' ), 5 );
        
        // Validación de documentos en checkout
        add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout_documents' ), 10, 2 );
        
        // Protección de webhooks
        add_action( 'woocommerce_api_megasoft_webhook', array( $this, 'protect_webhook_endpoint' ), 1 );
        
        // Protección de retorno de pago
        add_action( 'woocommerce_api_wc_gateway_megasoft_universal', array( $this, 'protect_return_endpoint' ), 1 );
        
        // Sanitización de parámetros GET/POST
        add_action( 'init', array( $this, 'sanitize_request_params' ), 1 );
        
        // Validación de números de control
        add_filter( 'megasoft_validate_control_number', array( $this, 'validate_control_number_hook' ), 10, 1 );
        
        // Protección AJAX admin
        add_action( 'wp_ajax_megasoft_admin_action', array( $this, 'protect_admin_ajax' ), 1 );
        
        // Rate limiting para formularios
        add_action( 'woocommerce_checkout_process', array( $this, 'apply_checkout_rate_limit' ), 1 );
        
        // Limpieza de logs de seguridad antiguos
        add_action( 'megasoft_daily_cleanup', array( $this, 'cleanup_security_logs' ) );
        
        // Protección contra SQL injection en búsquedas
        add_filter( 'posts_search', array( $this, 'secure_search_query' ), 10, 2 );
    }
    
    /**
     * Sanitizar datos del checkout
     */
    public function sanitize_checkout_data( $data ) {
        // Sanitizar campos de documento si existen
        if ( isset( $data['megasoft_document_type'] ) ) {
            $data['megasoft_document_type'] = $this->security->sanitize_input( 
                $data['megasoft_document_type'], 
                'alphanumeric' 
            );
        }
        
        if ( isset( $data['megasoft_document_number'] ) ) {
            $data['megasoft_document_number'] = $this->security->sanitize_input( 
                $data['megasoft_document_number'], 
                'document_number' 
            );
        }
        
        if ( isset( $data['megasoft_installments'] ) ) {
            $data['megasoft_installments'] = $this->security->sanitize_input( 
                $data['megasoft_installments'], 
                'int' 
            );
        }
        
        return $data;
    }
    
    /**
     * Validar documentos en checkout
     */
    public function validate_checkout_documents( $data, $errors ) {
        // Solo validar si se está usando MegaSoft Gateway
        if ( ! isset( $data['payment_method'] ) || $data['payment_method'] !== 'megasoft_gateway_universal' ) {
            return;
        }
        
        // Validar documento si está presente
        if ( isset( $data['megasoft_document_type'] ) && isset( $data['megasoft_document_number'] ) ) {
            $validation = $this->security->validate_document( 
                $data['megasoft_document_type'], 
                $data['megasoft_document_number'] 
            );
            
            if ( ! $validation['valid'] ) {
                $errors->add( 'megasoft_document', $validation['message'] );
                
                $this->security->log_security_event( 'invalid_document_checkout', array(
                    'document_type' => $data['megasoft_document_type'],
                    'error' => $validation['message']
                ) );
            }
        }
    }
    
    /**
     * Proteger endpoint de webhook
     */
    public function protect_webhook_endpoint() {
        // Verificar IP si está configurado
        $allowed_ips = apply_filters( 'megasoft_webhook_allowed_ips', array() );
        
        if ( ! empty( $allowed_ips ) ) {
            $client_ip = $this->security->get_client_ip();
            
            if ( ! in_array( $client_ip, $allowed_ips, true ) ) {
                $this->security->log_security_event( 'webhook_unauthorized_ip', array(
                    'ip' => $client_ip,
                    'allowed_ips' => $allowed_ips
                ) );
                
                status_header( 403 );
                wp_die( 'Forbidden', 'Forbidden', array( 'response' => 403 ) );
            }
        }
        
        // Verificar rate limiting
        $rate_limit = $this->security->check_rate_limit( 
            'webhook_' . $this->security->get_client_ip(), 
            10, // 10 peticiones
            60  // por minuto
        );
        
        if ( ! $rate_limit['allowed'] ) {
            $this->security->log_security_event( 'webhook_rate_limit_exceeded', array(
                'ip' => $this->security->get_client_ip(),
                'reset_time' => $rate_limit['reset_time']
            ) );
            
            status_header( 429 );
            header( 'Retry-After: ' . ( $rate_limit['reset_time'] - time() ) );
            wp_die( 'Too Many Requests', 'Too Many Requests', array( 'response' => 429 ) );
        }
        
        // Verificar método HTTP
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
            $this->security->log_security_event( 'webhook_invalid_method', array(
                'method' => $_SERVER['REQUEST_METHOD']
            ) );
            
            status_header( 405 );
            wp_die( 'Method Not Allowed', 'Method Not Allowed', array( 'response' => 405 ) );
        }
    }
    
    /**
     * Proteger endpoint de retorno
     */
    public function protect_return_endpoint() {
        // Verificar que vengan los parámetros necesarios
        if ( ! isset( $_GET['control'] ) || ! isset( $_GET['factura'] ) ) {
            $this->security->log_security_event( 'return_missing_params', array(
                'get_params' => array_keys( $_GET )
            ) );
            
            wp_die( 
                esc_html__( 'Parámetros inválidos', 'woocommerce-megasoft-gateway-universal' ), 
                'Invalid Parameters', 
                array( 'response' => 400 ) 
            );
        }
        
        // Sanitizar parámetros
        $_GET['control'] = $this->security->sanitize_input( $_GET['control'], 'control_number' );
        $_GET['factura'] = $this->security->sanitize_input( $_GET['factura'], 'int' );
        
        // Validar número de control
        if ( ! $this->security->validate_control_number( $_GET['control'] ) ) {
            $this->security->log_security_event( 'return_invalid_control', array(
                'control' => $_GET['control'],
                'factura' => $_GET['factura']
            ) );
            
            wp_die( 
                esc_html__( 'Número de control inválido', 'woocommerce-megasoft-gateway-universal' ), 
                'Invalid Control Number', 
                array( 'response' => 400 ) 
            );
        }
        
        // Rate limiting por IP
        $rate_limit = $this->security->check_rate_limit( 
            'return_' . $this->security->get_client_ip(), 
            5,   // 5 peticiones
            300  // por 5 minutos
        );
        
        if ( ! $rate_limit['allowed'] ) {
            $this->security->log_security_event( 'return_rate_limit_exceeded', array(
                'control' => $_GET['control'],
                'ip' => $this->security->get_client_ip()
            ) );
            
            wp_die( 
                esc_html__( 'Demasiadas solicitudes. Por favor, espere unos minutos.', 'woocommerce-megasoft-gateway-universal' ), 
                'Too Many Requests', 
                array( 'response' => 429 ) 
            );
        }
    }
    
    /**
     * Sanitizar parámetros de request
     */
    public function sanitize_request_params() {
        // Solo para rutas de MegaSoft
        if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
            return;
        }
        
        $request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
        
        if ( strpos( $request_uri, 'megasoft' ) === false && strpos( $request_uri, 'wc-api' ) === false ) {
            return;
        }
        
        // Sanitizar $_GET
        if ( ! empty( $_GET ) ) {
            foreach ( $_GET as $key => $value ) {
                $clean_key = sanitize_key( $key );
                
                if ( $clean_key !== $key ) {
                    unset( $_GET[ $key ] );
                    $_GET[ $clean_key ] = $value;
                    $key = $clean_key;
                }
                
                // Sanitizar según el tipo de parámetro
                if ( in_array( $key, array( 'control', 'control_number' ), true ) ) {
                    $_GET[ $key ] = $this->security->sanitize_input( $value, 'control_number' );
                } elseif ( in_array( $key, array( 'factura', 'order_id', 'id' ), true ) ) {
                    $_GET[ $key ] = $this->security->sanitize_input( $value, 'int' );
                } else {
                    $_GET[ $key ] = $this->security->sanitize_input( $value, 'text' );
                }
            }
        }
        
        // Sanitizar $_POST para peticiones MegaSoft
        if ( ! empty( $_POST ) && ( strpos( $request_uri, 'megasoft' ) !== false || strpos( $request_uri, 'checkout' ) !== false ) ) {
            foreach ( $_POST as $key => $value ) {
                if ( strpos( $key, 'megasoft_' ) === 0 ) {
                    $field_type = 'text';
                    
                    if ( strpos( $key, 'document_number' ) !== false ) {
                        $field_type = 'document_number';
                    } elseif ( strpos( $key, 'document_type' ) !== false ) {
                        $field_type = 'alphanumeric';
                    } elseif ( strpos( $key, 'installments' ) !== false ) {
                        $field_type = 'int';
                    }
                    
                    $_POST[ $key ] = $this->security->sanitize_input( $value, $field_type );
                }
            }
        }
    }
    
    /**
     * Validar número de control mediante hook
     */
    public function validate_control_number_hook( $control_number ) {
        return $this->security->validate_control_number( $control_number );
    }
    
    /**
     * Proteger AJAX admin
     */
    public function protect_admin_ajax() {
        // Verificar capacidades
        if ( ! current_user_can( 'manage_options' ) ) {
            $this->security->log_security_event( 'admin_ajax_unauthorized', array(
                'user_id' => get_current_user_id(),
                'action' => isset( $_POST['admin_action'] ) ? sanitize_text_field( wp_unslash( $_POST['admin_action'] ) ) : ''
            ) );
            
            wp_send_json_error( array(
                'message' => __( 'Permisos insuficientes', 'woocommerce-megasoft-gateway-universal' )
            ) );
        }
        
        // Verificar nonce
        if ( ! isset( $_POST['nonce'] ) || ! $this->security->verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'megasoft_admin' ) ) {
            $this->security->log_security_event( 'admin_ajax_invalid_nonce', array(
                'user_id' => get_current_user_id()
            ) );
            
            wp_send_json_error( array(
                'message' => __( 'Verificación de seguridad fallida', 'woocommerce-megasoft-gateway-universal' )
            ) );
        }
        
        // Rate limiting por usuario
        $rate_limit = $this->security->check_rate_limit( 
            'admin_ajax_' . get_current_user_id(), 
            30,  // 30 peticiones
            60   // por minuto
        );
        
        if ( ! $rate_limit['allowed'] ) {
            $this->security->log_security_event( 'admin_ajax_rate_limit', array(
                'user_id' => get_current_user_id()
            ) );
            
            wp_send_json_error( array(
                'message' => __( 'Demasiadas peticiones. Espere un momento.', 'woocommerce-megasoft-gateway-universal' )
            ) );
        }
    }
    
    /**
     * Aplicar rate limiting en checkout
     */
    public function apply_checkout_rate_limit() {
        // Solo para MegaSoft Gateway
        if ( ! isset( $_POST['payment_method'] ) || $_POST['payment_method'] !== 'megasoft_gateway_universal' ) {
            return;
        }
        
        $ip = $this->security->get_client_ip();
        
        // Rate limiting: 3 intentos por IP cada 5 minutos
        $rate_limit = $this->security->check_rate_limit( 
            'checkout_' . $ip, 
            3,   // 3 intentos
            300  // por 5 minutos
        );
        
        if ( ! $rate_limit['allowed'] ) {
            $this->security->log_security_event( 'checkout_rate_limit_exceeded', array(
                'ip' => $ip,
                'reset_time' => $rate_limit['reset_time']
            ) );
            
            wc_add_notice( 
                sprintf(
                    __( 'Demasiados intentos de pago. Por favor, espere %d minutos antes de intentar nuevamente.', 'woocommerce-megasoft-gateway-universal' ),
                    ceil( ( $rate_limit['reset_time'] - time() ) / 60 )
                ),
                'error' 
            );
            
            // Detener el proceso de checkout
            throw new Exception( 'Rate limit exceeded' );
        }
    }
    
    /**
     * Limpiar logs de seguridad antiguos
     */
    public function cleanup_security_logs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'megasoft_security_log';
        $days_to_keep = apply_filters( 'megasoft_security_log_retention_days', 90 );
        
        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days_to_keep
        ) );
        
        if ( $deleted > 0 && $this->logger ) {
            $this->logger->info( "Limpieza de logs de seguridad: {$deleted} registros eliminados" );
        }
    }
    
    /**
     * Proteger consultas de búsqueda
     */
    public function secure_search_query( $search, $query ) {
        global $wpdb;
        
        if ( ! is_admin() || ! $query->is_search() ) {
            return $search;
        }
        
        // Solo para búsquedas de MegaSoft
        if ( ! isset( $_GET['s'] ) || strpos( $_GET['s'], 'megasoft' ) === false ) {
            return $search;
        }
        
        // Escapar búsqueda
        $search_term = $this->security->sanitize_input( $_GET['s'], 'text' );
        
        // Registrar búsquedas sospechosas
        if ( $this->is_suspicious_search( $search_term ) ) {
            $this->security->log_security_event( 'suspicious_search', array(
                'search_term' => $search_term,
                'user_id' => get_current_user_id()
            ) );
        }
        
        return $search;
    }
    
    /**
     * Detectar búsquedas sospechosas (SQL injection, XSS)
     */
    private function is_suspicious_search( $search_term ) {
        $suspicious_patterns = array(
            '/union\s+select/i',
            '/drop\s+table/i',
            '/insert\s+into/i',
            '/delete\s+from/i',
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i', // eventos JS
            '/\.\.\//',      // path traversal
        );
        
        foreach ( $suspicious_patterns as $pattern ) {
            if ( preg_match( $pattern, $search_term ) ) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Agregar filtros para sanitización en metadatos
     */
    public function sanitize_order_meta( $meta_value, $meta_key ) {
        // Solo para metas de MegaSoft
        if ( strpos( $meta_key, '_megasoft_' ) !== 0 ) {
            return $meta_value;
        }
        
        // Sanitizar según el tipo de meta
        if ( strpos( $meta_key, 'control_number' ) !== false ) {
            return $this->security->sanitize_input( $meta_value, 'control_number' );
        } elseif ( strpos( $meta_key, 'document_number' ) !== false ) {
            return $this->security->sanitize_input( $meta_value, 'document_number' );
        } elseif ( strpos( $meta_key, 'auth_id' ) !== false || strpos( $meta_key, 'reference' ) !== false ) {
            return $this->security->sanitize_input( $meta_value, 'alphanumeric' );
        }
        
        return $this->security->sanitize_input( $meta_value, 'text' );
    }
}

// Inicializar hooks después de que la clase de seguridad esté disponible
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'MegaSoft_Security' ) ) {
        new MegaSoft_Security_Hooks();
    }
}, 15 );