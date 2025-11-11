<?php
/**
 * MegaSoft Security Class
 * Clase de seguridad centralizada para validación y sanitización
 * 
 * @package WooCommerce_MegaSoft_Gateway
 * @version 3.0.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MegaSoft_Security {
    
    /**
     * Instancia única (Singleton)
     */
    private static $instance = null;
    
    /**
     * Logger
     */
    private $logger;
    
    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        if ( class_exists( 'MegaSoft_Logger' ) ) {
            $this->logger = new MegaSoft_Logger( false, 'warn' );
        }
        
        // Hooks de seguridad
        add_action( 'init', array( $this, 'security_headers' ) );
        add_filter( 'megasoft_sanitize_input', array( $this, 'sanitize_input' ), 10, 2 );
        add_filter( 'megasoft_validate_document', array( $this, 'validate_document' ), 10, 2 );
        add_filter( 'megasoft_validate_control_number', array( $this, 'validate_control_number' ), 10, 1 );
    }
    
    /**
     * Agregar headers de seguridad
     */
    public function security_headers() {
        if ( ! is_admin() && isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'wc-api' ) !== false ) {
            header( 'X-Content-Type-Options: nosniff' );
            header( 'X-Frame-Options: DENY' );
            header( 'X-XSS-Protection: 1; mode=block' );
            header( 'Referrer-Policy: strict-origin-when-cross-origin' );
        }
    }
    
    /**
     * Sanitizar entrada según tipo
     * 
     * @param mixed $value Valor a sanitizar
     * @param string $type Tipo de sanitización
     * @return mixed Valor sanitizado
     */
    public function sanitize_input( $value, $type = 'text' ) {
        if ( is_array( $value ) ) {
            return array_map( function( $v ) use ( $type ) {
                return $this->sanitize_input( $v, $type );
            }, $value );
        }
        
        switch ( $type ) {
            case 'email':
                return sanitize_email( $value );
                
            case 'url':
                return esc_url_raw( $value );
                
            case 'int':
            case 'number':
                return absint( $value );
                
            case 'float':
            case 'decimal':
                return floatval( $value );
                
            case 'alphanumeric':
                return preg_replace( '/[^a-zA-Z0-9]/', '', $value );
                
            case 'numeric':
                return preg_replace( '/[^0-9]/', '', $value );
                
            case 'control_number':
                return $this->sanitize_control_number( $value );
                
            case 'document_number':
                return $this->sanitize_document_number( $value );
                
            case 'html':
                return wp_kses_post( $value );
                
            case 'textarea':
                return sanitize_textarea_field( $value );
                
            case 'text':
            default:
                return sanitize_text_field( $value );
        }
    }
    
    /**
     * Sanitizar número de control
     */
    private function sanitize_control_number( $value ) {
        // Solo números, mínimo 10 dígitos
        $clean = preg_replace( '/[^0-9]/', '', $value );
        return strlen( $clean ) >= 10 ? $clean : '';
    }
    
    /**
     * Sanitizar número de documento
     */
    private function sanitize_document_number( $value ) {
        // Números y letras mayúsculas, sin espacios
        return strtoupper( preg_replace( '/[^A-Z0-9]/i', '', $value ) );
    }
    
    /**
     * Validar documento de identidad
     * 
     * @param string $type Tipo de documento (V, E, J, G, P, C)
     * @param string $number Número de documento
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validate_document( $type, $number ) {
        // Sanitizar inputs
        $type = strtoupper( sanitize_text_field( $type ) );
        $number = $this->sanitize_document_number( $number );
        
        // Validaciones según tipo
        $validations = array(
            'V' => array(
                'pattern' => '/^[0-9]{6,9}$/',
                'label' => __( 'Cédula Venezolana', 'woocommerce-megasoft-gateway-universal' ),
                'message' => __( 'La cédula debe tener entre 6 y 9 dígitos', 'woocommerce-megasoft-gateway-universal' )
            ),
            'E' => array(
                'pattern' => '/^[0-9]{6,9}$/',
                'label' => __( 'Cédula Extranjera', 'woocommerce-megasoft-gateway-universal' ),
                'message' => __( 'La cédula debe tener entre 6 y 9 dígitos', 'woocommerce-megasoft-gateway-universal' )
            ),
            'J' => array(
                'pattern' => '/^[0-9]{9,10}$/',
                'label' => __( 'RIF Jurídico', 'woocommerce-megasoft-gateway-universal' ),
                'message' => __( 'El RIF debe tener entre 9 y 10 dígitos', 'woocommerce-megasoft-gateway-universal' )
            ),
            'G' => array(
                'pattern' => '/^[0-9]{9,10}$/',
                'label' => __( 'RIF Gubernamental', 'woocommerce-megasoft-gateway-universal' ),
                'message' => __( 'El RIF debe tener entre 9 y 10 dígitos', 'woocommerce-megasoft-gateway-universal' )
            ),
            'P' => array(
                'pattern' => '/^[A-Z0-9]{6,15}$/',
                'label' => __( 'Pasaporte', 'woocommerce-megasoft-gateway-universal' ),
                'message' => __( 'El pasaporte debe tener entre 6 y 15 caracteres alfanuméricos', 'woocommerce-megasoft-gateway-universal' )
            ),
            'C' => array(
                'pattern' => '/^[0-9]{9,10}$/',
                'label' => __( 'RIF Consorcio', 'woocommerce-megasoft-gateway-universal' ),
                'message' => __( 'El RIF debe tener entre 9 y 10 dígitos', 'woocommerce-megasoft-gateway-universal' )
            ),
        );
        
        // Verificar que el tipo sea válido
        if ( ! isset( $validations[ $type ] ) ) {
            return array(
                'valid' => false,
                'message' => __( 'Tipo de documento inválido', 'woocommerce-megasoft-gateway-universal' )
            );
        }
        
        $validation = $validations[ $type ];
        
        // Validar patrón
        if ( ! preg_match( $validation['pattern'], $number ) ) {
            return array(
                'valid' => false,
                'message' => $validation['message']
            );
        }
        
        return array(
            'valid' => true,
            'message' => 'OK'
        );
    }
    
    /**
     * Validar número de control
     * 
     * @param string $control_number Número de control
     * @return bool True si es válido
     */
    public function validate_control_number( $control_number ) {
        $clean = $this->sanitize_control_number( $control_number );
        
        // Debe ser numérico y tener al menos 10 dígitos
        if ( empty( $clean ) || strlen( $clean ) < 10 ) {
            return false;
        }
        
        // Validación adicional: no puede ser todo ceros o secuencia repetida
        if ( preg_match( '/^(.)\1+$/', $clean ) ) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validar nonce personalizado
     * 
     * @param string $nonce Nonce a validar
     * @param string $action Acción del nonce
     * @return bool True si es válido
     */
    public function verify_nonce( $nonce, $action ) {
        $valid = wp_verify_nonce( $nonce, $action );
        
        if ( ! $valid && $this->logger ) {
            $this->logger->warn( "Nonce inválido detectado", array(
                'action' => $action,
                'ip' => $this->get_client_ip(),
                'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : ''
            ) );
        }
        
        return $valid;
    }
    
    /**
     * Validar origin/referer para prevenir CSRF
     * 
     * @return bool True si el origin es válido
     */
    public function validate_origin() {
        $site_url = get_site_url();
        
        // Verificar HTTP_REFERER
        if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
            $referer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
            if ( strpos( $referer, $site_url ) !== 0 ) {
                if ( $this->logger ) {
                    $this->logger->warn( "Referer inválido detectado", array(
                        'referer' => $referer,
                        'expected' => $site_url,
                        'ip' => $this->get_client_ip()
                    ) );
                }
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Rate limiting para prevenir ataques de fuerza bruta
     * 
     * @param string $key Clave única para el rate limit
     * @param int $max_attempts Máximo de intentos permitidos
     * @param int $time_window Ventana de tiempo en segundos
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_time' => int]
     */
    public function check_rate_limit( $key, $max_attempts = 5, $time_window = 300 ) {
        $transient_key = 'megasoft_rl_' . md5( $key );
        $attempts = get_transient( $transient_key );
        
        if ( false === $attempts ) {
            $attempts = array(
                'count' => 0,
                'first_attempt' => time()
            );
        }
        
        $elapsed_time = time() - $attempts['first_attempt'];
        
        // Si ha pasado la ventana de tiempo, resetear
        if ( $elapsed_time > $time_window ) {
            $attempts = array(
                'count' => 1,
                'first_attempt' => time()
            );
            set_transient( $transient_key, $attempts, $time_window );
            
            return array(
                'allowed' => true,
                'remaining' => $max_attempts - 1,
                'reset_time' => time() + $time_window
            );
        }
        
        // Incrementar contador
        $attempts['count']++;
        set_transient( $transient_key, $attempts, $time_window );
        
        $allowed = $attempts['count'] <= $max_attempts;
        $remaining = max( 0, $max_attempts - $attempts['count'] );
        $reset_time = $attempts['first_attempt'] + $time_window;
        
        if ( ! $allowed && $this->logger ) {
            $this->logger->warn( "Rate limit excedido", array(
                'key' => $key,
                'attempts' => $attempts['count'],
                'max' => $max_attempts,
                'ip' => $this->get_client_ip()
            ) );
        }
        
        return array(
            'allowed' => $allowed,
            'remaining' => $remaining,
            'reset_time' => $reset_time
        );
    }
    
    /**
     * Detectar y bloquear IPs sospechosas
     * 
     * @param string $ip Dirección IP
     * @return bool True si la IP está bloqueada
     */
    public function is_ip_blocked( $ip ) {
        $blocked_ips = get_option( 'megasoft_blocked_ips', array() );
        
        if ( in_array( $ip, $blocked_ips, true ) ) {
            if ( $this->logger ) {
                $this->logger->warn( "Intento de acceso desde IP bloqueada", array(
                    'ip' => $ip,
                    'uri' => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''
                ) );
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Bloquear IP
     * 
     * @param string $ip Dirección IP
     * @param string $reason Motivo del bloqueo
     */
    public function block_ip( $ip, $reason = '' ) {
        $blocked_ips = get_option( 'megasoft_blocked_ips', array() );
        
        if ( ! in_array( $ip, $blocked_ips, true ) ) {
            $blocked_ips[] = $ip;
            update_option( 'megasoft_blocked_ips', $blocked_ips );
            
            if ( $this->logger ) {
                $this->logger->info( "IP bloqueada", array(
                    'ip' => $ip,
                    'reason' => $reason
                ) );
            }
        }
    }
    
    /**
     * Validar datos de transacción
     * 
     * @param array $data Datos de la transacción
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate_transaction_data( $data ) {
        $errors = array();
        
        // Validar monto
        if ( ! isset( $data['amount'] ) || ! is_numeric( $data['amount'] ) || $data['amount'] <= 0 ) {
            $errors[] = __( 'Monto inválido', 'woocommerce-megasoft-gateway-universal' );
        }
        
        // Validar order_id
        if ( ! isset( $data['order_id'] ) || ! is_numeric( $data['order_id'] ) || $data['order_id'] <= 0 ) {
            $errors[] = __( 'ID de orden inválido', 'woocommerce-megasoft-gateway-universal' );
        }
        
        // Validar documento si está presente
        if ( isset( $data['document_type'] ) && isset( $data['document_number'] ) ) {
            $doc_validation = $this->validate_document( $data['document_type'], $data['document_number'] );
            if ( ! $doc_validation['valid'] ) {
                $errors[] = $doc_validation['message'];
            }
        }
        
        // Validar nombre del cliente
        if ( isset( $data['client_name'] ) && strlen( $data['client_name'] ) < 3 ) {
            $errors[] = __( 'Nombre del cliente inválido', 'woocommerce-megasoft-gateway-universal' );
        }
        
        return array(
            'valid' => empty( $errors ),
            'errors' => $errors
        );
    }
    
    /**
     * Escapar salida HTML de forma segura
     * 
     * @param mixed $value Valor a escapar
     * @param string $context Contexto (html, attr, url, js)
     * @return mixed Valor escapado
     */
    public function escape_output( $value, $context = 'html' ) {
        if ( is_array( $value ) ) {
            return array_map( function( $v ) use ( $context ) {
                return $this->escape_output( $v, $context );
            }, $value );
        }
        
        switch ( $context ) {
            case 'attr':
            case 'attribute':
                return esc_attr( $value );
                
            case 'url':
                return esc_url( $value );
                
            case 'js':
            case 'javascript':
                return esc_js( $value );
                
            case 'textarea':
                return esc_textarea( $value );
                
            case 'html':
            default:
                return esc_html( $value );
        }
    }
    
    /**
     * Obtener IP del cliente de forma segura
     * 
     * @return string IP del cliente
     */
    public function get_client_ip() {
        $ip_fields = array(
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        );
        
        foreach ( $ip_fields as $field ) {
            if ( ! empty( $_SERVER[ $field ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $field ] ) );
                
                // Si hay múltiples IPs, tomar la primera
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = explode( ',', $ip )[0];
                }
                
                // Validar formato de IP
                $ip = trim( $ip );
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                    return $ip;
                }
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Generar hash seguro para verificación
     * 
     * @param array $data Datos a hashear
     * @param string $secret Secreto (opcional)
     * @return string Hash generado
     */
    public function generate_secure_hash( $data, $secret = '' ) {
        if ( empty( $secret ) ) {
            $secret = wp_salt( 'auth' );
        }
        
        $data_string = is_array( $data ) ? wp_json_encode( $data ) : $data;
        return hash_hmac( 'sha256', $data_string, $secret );
    }
    
    /**
     * Verificar hash seguro
     * 
     * @param array $data Datos a verificar
     * @param string $hash Hash a comparar
     * @param string $secret Secreto (opcional)
     * @return bool True si el hash es válido
     */
    public function verify_secure_hash( $data, $hash, $secret = '' ) {
        $expected_hash = $this->generate_secure_hash( $data, $secret );
        return hash_equals( $expected_hash, $hash );
    }
    
    /**
     * Limpiar y validar SQL dinámico (para casos donde no se puede usar prepare)
     * 
     * @param string $value Valor a limpiar
     * @param string $type Tipo de dato esperado
     * @return string Valor limpio
     */
    public function sanitize_sql_value( $value, $type = 'string' ) {
        global $wpdb;
        
        switch ( $type ) {
            case 'int':
            case 'integer':
                return absint( $value );
                
            case 'float':
            case 'decimal':
                return floatval( $value );
                
            case 'like':
                return '%' . $wpdb->esc_like( $value ) . '%';
                
            case 'identifier':
                // Para nombres de tablas/columnas
                return preg_replace( '/[^a-zA-Z0-9_]/', '', $value );
                
            case 'string':
            default:
                return $wpdb->_real_escape( $value );
        }
    }
    
    /**
     * Registrar evento de seguridad
     * 
     * @param string $event_type Tipo de evento
     * @param array $details Detalles del evento
     */
    public function log_security_event( $event_type, $details = array() ) {
        if ( ! $this->logger ) {
            return;
        }
        
        $event_data = array_merge(
            array(
                'event_type' => $event_type,
                'timestamp' => current_time( 'mysql' ),
                'ip' => $this->get_client_ip(),
                'user_id' => get_current_user_id(),
                'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
                'request_uri' => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''
            ),
            $details
        );
        
        $this->logger->warn( "Evento de seguridad: {$event_type}", $event_data );
        
        // Almacenar en base de datos para auditoría
        global $wpdb;
        $table = $wpdb->prefix . 'megasoft_security_log';
        
        // Crear tabla si no existe
        $this->create_security_log_table();
        
        $wpdb->insert(
            $table,
            array(
                'event_type' => $event_type,
                'event_data' => wp_json_encode( $event_data ),
                'ip_address' => $this->get_client_ip(),
                'user_id' => get_current_user_id(),
                'created_at' => current_time( 'mysql' )
            ),
            array( '%s', '%s', '%s', '%d', '%s' )
        );
    }
    
    /**
     * Crear tabla de log de seguridad
     */
    private function create_security_log_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'megasoft_security_log';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_data longtext NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_id bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY ip_address (ip_address),
            KEY created_at (created_at)
        ) {$charset_collate};";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}

// Función helper global
function megasoft_security() {
    return MegaSoft_Security::get_instance();
}

// Inicializar
add_action( 'plugins_loaded', array( 'MegaSoft_Security', 'get_instance' ), 5 );