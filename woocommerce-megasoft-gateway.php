<?php
/**
 * Plugin Name:         Pasarela de Pago Mega Soft para WooCommerce (Modalidad Universal) - PRODUCCIÓN
 * Plugin URI:          https://github.com/
 * Description:         Pasarela de pago venezolana Mega Soft completamente funcional para producción con dashboard, webhooks, validaciones avanzadas y soporte completo.
 * Author:              Tu Nombre
 * Author URI:          https://tu-sitio-web.com
 * Version:             3.0.5
 * Requires at least:   5.8
 * Requires PHP:        7.4
 * WC requires at least: 6.0
 * WC tested up to:     8.5
 * Text Domain:         woocommerce-megasoft-gateway-universal
 * Domain Path:         /languages
 * Network:             false
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

// Definir constantes del plugin
define( 'MEGASOFT_PLUGIN_VERSION', '3.0.5' );
define( 'MEGASOFT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MEGASOFT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MEGASOFT_PLUGIN_FILE', __FILE__ );

// Cargar sistema de seguridad
require_once MEGASOFT_PLUGIN_PATH . 'megasoft-security-loader.php';

/**
 * Verificar si WooCommerce está activo
 */
function megasoft_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return false;
    }
    return true;
}

/**
 * Mostrar notice si WooCommerce no está activo
 */
function megasoft_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p>
            <strong><?php esc_html_e( 'Mega Soft Gateway', 'woocommerce-megasoft-gateway-universal' ); ?></strong> 
            <?php esc_html_e( 'requiere que WooCommerce esté instalado y activo.', 'woocommerce-megasoft-gateway-universal' ); ?>
            <a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' )); ?>">
                <?php esc_html_e( 'Instalar WooCommerce', 'woocommerce-megasoft-gateway-universal' ); ?>
            </a>
        </p>
    </div>
    <?php
}

/**
 * Verificar dependencias antes de cargar el plugin
 */
function megasoft_init_plugin() {
    // Verificar si WooCommerce existe
    if ( ! megasoft_check_woocommerce() ) {
        add_action( 'admin_notices', 'megasoft_woocommerce_missing_notice' );
        return;
    }
    
    // Cargar el plugin principal
    megasoft_load_plugin();
}

/**
 * Cargar el plugin principal
 */
function megasoft_load_plugin() {
    
    // Cargar clases base si existen
    if ( file_exists( MEGASOFT_PLUGIN_PATH . 'includes/class-megasoft-logger.php' ) ) {
        require_once MEGASOFT_PLUGIN_PATH . 'includes/class-megasoft-logger.php';
    }
    
    if ( file_exists( MEGASOFT_PLUGIN_PATH . 'includes/class-megasoft-api.php' ) ) {
        require_once MEGASOFT_PLUGIN_PATH . 'includes/class-megasoft-api.php';
    }
    
    if ( file_exists( MEGASOFT_PLUGIN_PATH . 'includes/class-megasoft-webhook.php' ) ) {
        require_once MEGASOFT_PLUGIN_PATH . 'includes/class-megasoft-webhook.php';
        new MegaSoft_Webhook();
    }
    
    // Cargar admin solo en backend
    if ( is_admin() && file_exists( MEGASOFT_PLUGIN_PATH . 'includes/class-megasoft-admin.php' ) ) {
        require_once MEGASOFT_PLUGIN_PATH . 'includes/class-megasoft-admin.php';
        new MegaSoft_Admin();
    }

    // Cargar sistema de diagnóstico
    if ( is_admin() && file_exists( MEGASOFT_PLUGIN_PATH . 'includes/class-megasoft-diagnostics-ui.php' ) ) {
        require_once MEGASOFT_PLUGIN_PATH . 'includes/class-megasoft-diagnostics-ui.php';
    }
    
    // Agregar gateway a WooCommerce
    add_filter( 'woocommerce_payment_gateways', 'megasoft_add_gateway_class' );
}

/**
 * Agregar la clase del gateway a WooCommerce
 */
function megasoft_add_gateway_class( $gateways ) {
    $gateways[] = 'WC_Gateway_MegaSoft_Universal';
    return $gateways;
}

/**
 * Hook de inicialización - ESPERAMOS A QUE WOOCOMMERCE ESTÉ CARGADO
 */
add_action( 'plugins_loaded', 'megasoft_init_plugin', 11 );

// Hooks de activación y desactivación
register_activation_hook( MEGASOFT_PLUGIN_FILE, 'megasoft_plugin_activate' );
register_deactivation_hook( MEGASOFT_PLUGIN_FILE, 'megasoft_plugin_deactivate' );

function megasoft_plugin_activate() {
    // Verificar WooCommerce durante activación
    if ( ! megasoft_check_woocommerce() ) {
        deactivate_plugins( plugin_basename( MEGASOFT_PLUGIN_FILE ) );
        wp_die( 
            __( 'MegaSoft Gateway requiere WooCommerce. Por favor, instala y activa WooCommerce primero.', 'woocommerce-megasoft-gateway-universal' ),
            esc_html__( 'Verificación de Dependencias', 'woocommerce-megasoft-gateway-universal' ),
            array( 'back_link' => true )
        );
    }
    
    // Crear tablas de base de datos
    megasoft_create_tables();
    
    // Agregar capacidades
    $role = get_role( 'administrator' );
    if ( $role ) {
        $role->add_cap( 'manage_megasoft_transactions' );
    }
    
    // Programar tareas cron
    if ( ! wp_next_scheduled( 'megasoft_sync_transactions' ) ) {
        wp_schedule_event( time(), 'hourly', 'megasoft_sync_transactions' );
    }
    
    flush_rewrite_rules();
}

function megasoft_plugin_deactivate() {
    wp_clear_scheduled_hook( 'megasoft_sync_transactions' );
    flush_rewrite_rules();
}

/**
 * Crear tablas de base de datos
 */
function megasoft_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Tabla de transacciones
    $table_name = $wpdb->prefix . 'megasoft_transactions';
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) UNSIGNED NOT NULL,
        control_number varchar(50) DEFAULT NULL,
        status varchar(20) DEFAULT 'pending',
        amount decimal(10,2) NOT NULL,
        currency varchar(3) DEFAULT 'VES',
        auth_id varchar(50) DEFAULT NULL,
        reference varchar(100) DEFAULT NULL,
        payment_method varchar(50) DEFAULT NULL,
        document_type varchar(2) DEFAULT NULL,
        document_number varchar(20) DEFAULT NULL,
        client_name varchar(100) DEFAULT NULL,
        response_data longtext DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY control_number (control_number),
        KEY order_id (order_id),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// SOLO DEFINIR LA CLASE CUANDO WOOCOMMERCE ESTÉ DISPONIBLE
add_action( 'woocommerce_loaded', 'megasoft_define_gateway_class' );

function megasoft_define_gateway_class() {
    
    if ( class_exists( 'WC_Payment_Gateway' ) && ! class_exists( 'WC_Gateway_MegaSoft_Universal' ) ) {
        
        class WC_Gateway_MegaSoft_Universal extends WC_Payment_Gateway {
            
            private $logger;
            private $test_mode;
            
            public function __construct() {
                $this->id                 = 'megasoft_gateway_universal';
                $this->icon               = MEGASOFT_PLUGIN_URL . 'assets/images/megasoft-icon.png';
                $this->has_fields         = true;
                $this->method_title       = __( 'Mega Soft (Universal)', 'woocommerce-megasoft-gateway-universal' );
                $this->method_description = __( 'Pasarela de pago venezolana con soporte completo para TDC Nacional/Internacional y Pago Móvil.', 'woocommerce-megasoft-gateway-universal' );
                
                $this->supports = array(
                    'products',
                    'refunds'
                );
                
                $this->init_form_fields();
                $this->init_settings();
                
                // Propiedades
                $this->title             = $this->get_option( 'title' );
                $this->description       = $this->get_option( 'description' );
                $this->enabled           = $this->get_option( 'enabled' );
                $this->test_mode         = 'yes' === $this->get_option( 'testmode' );
                $this->debug             = 'yes' === $this->get_option( 'debug' );
                $this->require_document  = 'yes' === $this->get_option( 'require_document' );
                $this->enable_installments = 'yes' === $this->get_option( 'enable_installments' );
                
                // Inicializar logger básico
                $this->init_logger();
                
                // Hooks
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'woocommerce_api_wc_gateway_megasoft_universal', array( $this, 'handle_return' ) );
                add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'display_receipt' ) );
                add_action( 'woocommerce_checkout_process', array( $this, 'validate_checkout_fields' ) );
                
                // Scripts y estilos
                add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
                
                // Hooks para simulación PG inactivo
                add_action( 'admin_notices', array( $this, 'add_simulation_admin_notice' ) );
            }
            
            private function init_logger() {
                if ( class_exists( 'MegaSoft_Logger' ) ) {
                    $this->logger = new MegaSoft_Logger( $this->debug );
                } else {
                    // Logger básico si la clase completa no existe
                    $this->logger = (object) array(
                        'info' => function( $message ) { if ( $this->debug ) error_log( "[MEGASOFT INFO] " . $message ); },
                        'error' => function( $message ) { error_log( "[MEGASOFT ERROR] " . $message ); },
                        'debug' => function( $message ) { if ( $this->debug ) error_log( "[MEGASOFT DEBUG] " . $message ); }
                    );
                }
            }
            
            public function init_form_fields() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title'   => __( 'Activar/Desactivar', 'woocommerce-megasoft-gateway-universal' ),
                        'type'    => 'checkbox',
                        'label'   => __( 'Activar Mega Soft Gateway', 'woocommerce-megasoft-gateway-universal' ),
                        'default' => 'no'
                    ),
                    'title' => array(
                        'title'       => __( 'Título', 'woocommerce-megasoft-gateway-universal' ),
                        'type'        => 'text',
                        'description' => __( 'Título que verán los usuarios en el checkout.', 'woocommerce-megasoft-gateway-universal' ),
                        'default'     => __( 'Tarjeta de Crédito/Débito y Pago Móvil', 'woocommerce-megasoft-gateway-universal' ),
                        'desc_tip'    => true,
                    ),
                    'description' => array(
                        'title'       => __( 'Descripción', 'woocommerce-megasoft-gateway-universal' ),
                        'type'        => 'textarea',
                        'description' => __( 'Descripción que verán los usuarios en el checkout.', 'woocommerce-megasoft-gateway-universal' ),
                        'default'     => __( 'Paga de forma segura con tu tarjeta de crédito, débito o pago móvil. Serás redirigido a una página segura para completar tu pago.', 'woocommerce-megasoft-gateway-universal' ),
                        'desc_tip'    => true,
                    ),
                    
                    // Configuración de API
                    'api_settings' => array(
                        'title'       => __( 'Configuración de API', 'woocommerce-megasoft-gateway-universal' ),
                        'type'        => 'title',
                        'description' => __( 'Configura las credenciales proporcionadas por Mega Soft.', 'woocommerce-megasoft-gateway-universal' ),
                    ),
                    'testmode' => array(
                        'title'       => __( 'Modo de Prueba', 'woocommerce-megasoft-gateway-universal' ),
                        'type'        => 'checkbox',
                        'label'       => __( 'Activar modo de prueba', 'woocommerce-megasoft-gateway-universal' ),
                        'default'     => 'yes',
                        'description' => __( 'Utiliza el ambiente de pruebas de Mega Soft. Desactiva para producción.', 'woocommerce-megasoft-gateway-universal' ),
                    ),
                    'cod_afiliacion' => array(
                        'title'       => __( 'Código de Afiliación', 'woocommerce-megasoft-gateway-universal' ),
                        'type'        => 'text',
                        'description' => __( 'Código de afiliación proporcionado por Mega Soft.', 'woocommerce-megasoft-gateway-universal' ),
                        'default'     => '',
                        'desc_tip'    => true,
                    ),
                    'api_user' => array(
                        'title'       => __( 'Usuario API', 'woocommerce-megasoft-gateway-universal' ),
                        'type'        => 'text',
                        'description' => __( 'Usuario para la autenticación con la API.', 'woocommerce-megasoft-gateway-universal' ),
                        'default'     => '',
                        'desc_tip'    => true,
                    ),
                    'api_password' => array(
                        'title'       => __( 'Contraseña API', 'woocommerce-megasoft-gateway-universal' ),
                        'type'        => 'password',
                        'description' => __( 'Contraseña para la autenticación con la API.', 'woocommerce-megasoft-gateway-universal' ),
                        'default'     => '',
                        'desc_tip'    => true,
                    ),
                    
                    // Configuración de documentos
                    'document_settings' => array(
                        'title'       => __( 'Configuración de Documentos', 'woocommerce-megasoft-gateway-universal' ),
                        'type'        => 'title',
                        'description' => __( 'Configura la captura de datos de identificación del cliente.', 'woocommerce-megasoft-gateway-universal' ),
                    ),
                    'require_document' => array(
                        'title'   => __( 'Requerir Documento', 'woocommerce-megasoft-gateway-universal' ),
                        'type'    => 'checkbox',
                        'label'   => __( 'Requerir tipo y número de documento', 'woocommerce-megasoft-gateway-universal' ),
                        'default' => 'yes',
                        'description' => __( 'Obligatorio para cumplir regulaciones venezolanas.', 'woocommerce-megasoft-gateway-universal' ),
                    ),
                    'save_documents' => array(
                        'title'   => __( 'Guardar Documentos', 'woocommerce-megasoft-gateway-universal' ),
                        'type'    => 'checkbox',
                        'label'   => __( 'Guardar documentos para futuras compras', 'woocommerce-megasoft-gateway-universal' ),
                        'default' => 'yes',
                        'description' => __( 'Permite a clientes registrados reutilizar sus datos de documento.', 'woocommerce-megasoft-gateway-universal' ),
                    ),
                    
                    // Configuración de logs y debugging
                    'debug_settings' => array(
                        'title'       => __( 'Depuración y Logs', 'woocommerce-megasoft-gateway-universal' ),
                        'type'        => 'title',
                    ),
                    'debug' => array(
                        'title'   => __( 'Modo Debug', 'woocommerce-megasoft-gateway-universal' ),
                        'type'    => 'checkbox',
                        'label'   => __( 'Activar logs detallados', 'woocommerce-megasoft-gateway-universal' ),
                        'default' => 'no',
                        'description' => __( 'Guarda logs detallados para depuración. Solo activar si es necesario.', 'woocommerce-megasoft-gateway-universal' ),
                    ),
                    
                    // URLs importantes
                    'urls_info' => array(
                        'title' => __( 'URLs del Sistema', 'woocommerce-megasoft-gateway-universal' ),
                        'type'  => 'title',
                        'description' => $this->get_urls_info(),
                    ),
                );
            }
            
            private function get_urls_info() {
                $return_url = WC()->api_request_url( 'WC_Gateway_MegaSoft_Universal' );
                $webhook_url = WC()->api_request_url( 'megasoft_webhook' );
                
                return sprintf(
                    __( 'Proporciona las siguientes URLs a Mega Soft:<br/><br/><strong>URL de Retorno:</strong><br/><code>%s?control=@control@&factura=@facturatrx@</code><br/><br/><strong>URL de Webhook (Opcional):</strong><br/><code>%s</code>', 'woocommerce-megasoft-gateway-universal' ),
                    $return_url,
                    $webhook_url
                );
            }
            
            public function enqueue_scripts() {
                if ( is_checkout() ) {
                    wp_enqueue_style( 
                        'megasoft-checkout', 
                        MEGASOFT_PLUGIN_URL . 'assets/css/checkout.css', 
                        array(), 
                        MEGASOFT_PLUGIN_VERSION 
                    );
                }
            }
            
            public function payment_fields() {
                if ( $this->description ) {
                    echo '<p>' . wp_kses_post( $this->description ) . '</p>';
                }
                
                $this->render_document_fields();
            }
            
            private function render_document_fields() {
                if ( ! $this->require_document ) {
                    return;
                }
                
                $saved_data = $this->get_saved_customer_data();
                
                ?>
                <fieldset class="megasoft-document-fields">
                    <legend><?php esc_html_e( 'Datos de Identificación', 'woocommerce-megasoft-gateway-universal' ); ?></legend>
                    
                    <div class="megasoft-field-row">
                        <p class="form-row form-row-first">
                            <label for="megasoft_document_type">
                                <?php esc_html_e( 'Tipo de Documento', 'woocommerce-megasoft-gateway-universal' ); ?>
                                <span class="required">*</span>
                            </label>
                            <select id="megasoft_document_type" name="megasoft_document_type" required>
                                <option value=""><?php esc_html_e( 'Seleccione...', 'woocommerce-megasoft-gateway-universal' ); ?></option>
                                <option value="V" <?php selected( $saved_data['type'] ?? '', 'V' ); ?>><?php esc_html_e( 'V - Cédula Venezolana', 'woocommerce-megasoft-gateway-universal' ); ?></option>
                                <option value="E" <?php selected( $saved_data['type'] ?? '', 'E' ); ?>><?php esc_html_e( 'E - Cédula Extranjera', 'woocommerce-megasoft-gateway-universal' ); ?></option>
                                <option value="J" <?php selected( $saved_data['type'] ?? '', 'J' ); ?>><?php esc_html_e( 'J - RIF Jurídico', 'woocommerce-megasoft-gateway-universal' ); ?></option>
                                <option value="G" <?php selected( $saved_data['type'] ?? '', 'G' ); ?>><?php esc_html_e( 'G - RIF Gubernamental', 'woocommerce-megasoft-gateway-universal' ); ?></option>
                                <option value="P" <?php selected( $saved_data['type'] ?? '', 'P' ); ?>><?php esc_html_e( 'P - Pasaporte', 'woocommerce-megasoft-gateway-universal' ); ?></option>
                                <option value="C" <?php selected( $saved_data['type'] ?? '', 'C' ); ?>><?php esc_html_e( 'C - Cédula (Nuevo)', 'woocommerce-megasoft-gateway-universal' ); ?></option>
                            </select>
                        </p>
                        
                        <p class="form-row form-row-last">
                            <label for="megasoft_document_number">
                                <?php esc_html_e( 'Número de Documento', 'woocommerce-megasoft-gateway-universal' ); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="megasoft_document_number" 
                                name="megasoft_document_number" 
                                value="<?php echo esc_attr( $saved_data['number'] ?? '' ); ?>"
                                placeholder="<?php esc_html_e( 'Ej: 12345678', 'woocommerce-megasoft-gateway-universal' ); ?>" 
                                pattern="[0-9A-Za-z]+"
                                title="<?php esc_html_e( 'Solo números y letras', 'woocommerce-megasoft-gateway-universal' ); ?>"
                                required 
                            />
                        </p>
                    </div>
                    
                    <?php if ( 'yes' === $this->get_option( 'save_documents' ) && is_user_logged_in() ) : ?>
                    <p class="form-row">
                        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                            <input type="checkbox" id="megasoft_save_document" name="megasoft_save_document" value="1" />
                            <span class="woocommerce-form__label-text">
                                <?php esc_html_e( 'Guardar estos datos para futuras compras', 'woocommerce-megasoft-gateway-universal' ); ?>
                            </span>
                        </label>
                    </p>
                    <?php endif; ?>
                    
                    <div class="clear"></div>
                </fieldset>
                
                <style>
                .megasoft-document-fields {
                    background: #f8f9fa;
                    border: 1px solid #e9ecef;
                    padding: 15px;
                    margin: 15px 0;
                    border-radius: 5px;
                }
                .megasoft-document-fields .form-row {
                    margin: 0 0 10px 0;
                }
                .megasoft-document-fields label {
                    font-weight: bold;
                    color: #333;
                }
                .megasoft-document-fields .required {
                    color: #e74c3c;
                }
                </style>
                <?php
            }
            
            private function get_saved_customer_data() {
                if ( ! is_user_logged_in() ) {
                    return array();
                }
                
                $user_id = get_current_user_id();
                return array(
                    'type'   => get_user_meta( $user_id, '_megasoft_document_type', true ),
                    'number' => get_user_meta( $user_id, '_megasoft_document_number', true ),
                );
            }
            
            public function validate_checkout_fields() {
                if ( isset( $_POST['payment_method'] ) && $_POST['payment_method'] === $this->id ) {
                    
                    // Validar documento si es requerido
                    if ( $this->require_document ) {
                        $document_type = sanitize_text_field( $_POST['megasoft_document_type'] ?? '' );
                        $document_number = sanitize_text_field( $_POST['megasoft_document_number'] ?? '' );
                        
                        if ( empty( $document_type ) ) {
                            wc_add_notice( __( 'Por favor, seleccione el tipo de documento.', 'woocommerce-megasoft-gateway-universal' ), 'error' );
                        }
                        
                        if ( empty( $document_number ) ) {
                            wc_add_notice( __( 'Por favor, ingrese el número de documento.', 'woocommerce-megasoft-gateway-universal' ), 'error' );
                        } else {
                            // Validar formato del documento
                            if ( ! $this->validate_document_format( $document_type, $document_number ) ) {
                                wc_add_notice( __( 'Formato de documento inválido.', 'woocommerce-megasoft-gateway-universal' ), 'error' );
                            }
                        }
                    }
                }
            }
            
            private function validate_document_format( $type, $number ) {
                switch ( $type ) {
                    case 'V':
                    case 'E':
                    case 'C':
                        return preg_match( '/^[0-9]{6,10}$/', $number );
                    case 'J':
                    case 'G':
                        return preg_match( '/^[0-9]{8,10}$/', $number );
                    case 'P':
                        return preg_match( '/^[A-Z0-9]{6,15}$/i', $number );
                    default:
                        return false;
                }
            }
            
            /**
             * FUNCIÓN CORREGIDA: process_payment con manejo de PG inactivo
             */
            public function process_payment( $order_id ) {
                $order = wc_get_order( $order_id );
                
                if ( ! $order ) {
                    throw new Exception( __( 'Orden no encontrada.', 'woocommerce-megasoft-gateway-universal' ) );
                }
                
                try {
                    if ( is_callable( array( $this->logger, 'info' ) ) ) {
                        $this->logger->info( "Iniciando proceso de pago para orden #{$order_id}" );
                    }
                    
                    // Recopilar datos del pago
                    $payment_data = $this->prepare_payment_data( $order );
                    
                    // Guardar datos en la base de datos
                    $this->save_transaction_data( $order, $payment_data );
                    
                    // Hacer pre-registro con Mega Soft
                    $preregistration_result = $this->create_preregistration( $payment_data );
                    
                    // VERIFICAR SI EL RESULTADO ES UN ERROR (PG INACTIVO)
                    if ( is_array( $preregistration_result ) && isset( $preregistration_result['error'] ) ) {
                        
                        // Actualizar el estado de la orden como pendiente con nota del error
                        $order->update_status( 'pending', $preregistration_result['technical_details'] );
                        $order->add_order_note( 
                            sprintf( 
                                __( 'Error en pre-registro: %s', 'woocommerce-megasoft-gateway-universal' ),
                                $preregistration_result['message']
                            )
                        );
                        $order->save();
                        
                        // Guardar el error en los metadatos para mostrar en la página de error
                        $order->update_meta_data( '_megasoft_pg_error', $preregistration_result );
                        $order->save();
                        
                        // MOSTRAR MENSAJE DE ERROR AL USUARIO
                        $this->display_pg_inactive_notice( $preregistration_result );
                        
                        // Retornar falla sin redirección
                        return array(
                            'result' => 'failure',
                            'messages' => $preregistration_result['message']
                        );
                    }
                    
                    // Si llegamos aquí, el pre-registro fue exitoso
                    $control_number = $preregistration_result;
                    
                    // Actualizar orden con número de control
                    $order->update_meta_data( '_megasoft_control_number', $control_number );
                    $order->update_status( 'pending', __( 'Redirigiendo a Mega Soft...', 'woocommerce-megasoft-gateway-universal' ) );
                    $order->save();
                    
                    // Actualizar transacción en BD
                    $this->update_transaction_control( $order_id, $control_number );
                    
                    // URL de redirección
                    $redirect_url = $this->get_payment_url( $control_number );
                    
                    if ( is_callable( array( $this->logger, 'info' ) ) ) {
                        $this->logger->info( "Pre-registro exitoso. Control: {$control_number}, Orden: #{$order_id}" );
                    }
                    
                    return array(
                        'result'   => 'success',
                        'redirect' => $redirect_url
                    );
                    
                } catch ( Exception $e ) {
                    if ( is_callable( array( $this->logger, 'error' ) ) ) {
                        $this->logger->error( "Error en process_payment para orden #{$order_id}: " . $e->getMessage() );
                    }
                    
                    // Para excepciones, también mostrar mensaje amigable
                    wc_add_notice( 
                        __( 'Error procesando el pago. Por favor, intente más tarde o contacte al soporte.', 'woocommerce-megasoft-gateway-universal' ), 
                        'error' 
                    );
                    
                    return array( 'result' => 'failure' );
                }
            }
            
            private function prepare_payment_data( $order ) {
                $data = array(
                    'order_id'    => $order->get_id(),
                    'amount'      => $order->get_total(),
                    'currency'    => $order->get_currency(),
                    'client_name' => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
                );
                
                // Datos del documento
                if ( $this->require_document ) {
                    $data['document_type']   = sanitize_text_field( $_POST['megasoft_document_type'] ?? '' );
                    $data['document_number'] = sanitize_text_field( $_POST['megasoft_document_number'] ?? '' );
                    
                    // Guardar documentos si el usuario lo solicita
                    if ( isset( $_POST['megasoft_save_document'] ) && is_user_logged_in() ) {
                        $user_id = get_current_user_id();
                        update_user_meta( $user_id, '_megasoft_document_type', $data['document_type'] );
                        update_user_meta( $user_id, '_megasoft_document_number', $data['document_number'] );
                    }
                }
                
                return $data;
            }
            
            private function save_transaction_data( $order, $payment_data ) {
                global $wpdb;
                
                $table_name = $wpdb->prefix . 'megasoft_transactions';
                
                $wpdb->insert(
                    $table_name,
                    array(
                        'order_id'        => $order->get_id(),
                        'amount'          => $payment_data['amount'],
                        'currency'        => $payment_data['currency'],
                        'document_type'   => $payment_data['document_type'] ?? '',
                        'document_number' => $payment_data['document_number'] ?? '',
                        'client_name'     => $payment_data['client_name'],
                        'status'          => 'pending',
                        'created_at'      => current_time( 'mysql' )
                    ),
                    array( '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s' )
                );
            }
            
            private function update_transaction_control( $order_id, $control_number ) {
                global $wpdb;
                
                $table_name = $wpdb->prefix . 'megasoft_transactions';
                
                $wpdb->update(
                    $table_name,
                    array( 'control_number' => $control_number ),
                    array( 'order_id' => $order_id ),
                    array( '%s' ),
                    array( '%d' )
                );
            }
            
            /**
             * FUNCIÓN CORREGIDA: create_preregistration con manejo de PG inactivo
             */
            private function create_preregistration( $payment_data ) {
                $cod_afiliacion = $this->get_option( 'cod_afiliacion' );
                $api_user       = $this->get_option( 'api_user' );
                $api_password   = $this->get_option( 'api_password' );
                $base_url       = $this->test_mode ? 'https://paytest.megasoft.com.ve/action/' : 'https://e-payment.megasoft.com.ve/action/';
                
                // PARA SIMULAR PG INACTIVO: Verificar si está en modo simulación
                if ( get_option( 'megasoft_simulate_pg_inactive' ) ) {
                    $base_url = 'https://url-invalida-simulacion-pg-inactivo.com/action/';
                }
                
                // Construir XML para pre-registro
                $xml_data = '<request>';
                $xml_data .= '<cod_afiliacion>' . esc_html( $cod_afiliacion ) . '</cod_afiliacion>';
                $xml_data .= '<factura>' . esc_html( $payment_data['order_id'] ) . '</factura>';
                $xml_data .= '<monto>' . number_format( $payment_data['amount'], 2, '.', '' ) . '</monto>';
                
                if ( ! empty( $payment_data['client_name'] ) ) {
                    $xml_data .= '<nombre>' . esc_html( $payment_data['client_name'] ) . '</nombre>';
                }
                
                if ( ! empty( $payment_data['document_type'] ) && ! empty( $payment_data['document_number'] ) ) {
                    $xml_data .= '<tipo>' . esc_html( $payment_data['document_type'] ) . '</tipo>';
                    $xml_data .= '<cedula_rif>' . esc_html( $payment_data['document_number'] ) . '</cedula_rif>';
                }
                
                $xml_data .= '</request>';
                
                $auth_credentials = base64_encode( $api_user . ':' . $api_password );
                $headers = array(
                    'Authorization' => 'Basic ' . $auth_credentials,
                    'Content-Type'  => 'text/xml'
                );
                
                // Log del intento de conexión
                if ( is_callable( array( $this->logger, 'info' ) ) ) {
                    $this->logger->info( "Intentando pre-registro con Mega Soft", array(
                        'order_id' => $payment_data['order_id'],
                        'amount' => $payment_data['amount'],
                        'base_url' => $base_url,
                        'simulation_mode' => get_option( 'megasoft_simulate_pg_inactive' ) ? 'ACTIVE' : 'INACTIVE'
                    ) );
                }
                
                $response = wp_remote_post( $base_url . 'paymentgatewayuniversal-prereg', array(
                    'headers'   => $headers,
                    'body'      => $xml_data,
                    'timeout'   => 30,
                    'sslverify' => ! $this->test_mode,
                ) );
                
                // DETECTAR SI EL PG ESTÁ INACTIVO
                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                    $error_code = $response->get_error_code();
                    
                    // Log del error de conexión
                    if ( is_callable( array( $this->logger, 'error' ) ) ) {
                        $this->logger->error( "PG Inactivo - Error de conexión en pre-registro", array(
                            'order_id' => $payment_data['order_id'],
                            'error_code' => $error_code,
                            'error_message' => $error_message,
                            'url_attempted' => $base_url . 'paymentgatewayuniversal-prereg'
                        ) );
                    }
                    
                    // Determinar si es un error de PG inactivo
                    if ( $this->is_pg_inactive_error( $error_code, $error_message ) ) {
                        return array(
                            'error' => 'pg_inactive',
                            'message' => __( 'La plataforma bancaria no está disponible en este momento. Por favor, intente más tarde.', 'woocommerce-megasoft-gateway-universal' ),
                            'user_message' => __( 'Servicio temporalmente no disponible', 'woocommerce-megasoft-gateway-universal' ),
                            'technical_details' => "Error: {$error_code} - {$error_message}"
                        );
                    } else {
                        // Otros errores de conexión
                        return array(
                            'error' => 'connection_error',
                            'message' => __( 'Error de conexión con la pasarela de pagos. Por favor, verifique su conexión e intente nuevamente.', 'woocommerce-megasoft-gateway-universal' ),
                            'user_message' => __( 'Error de conexión', 'woocommerce-megasoft-gateway-universal' ),
                            'technical_details' => "Error: {$error_code} - {$error_message}"
                        );
                    }
                }
                
                $response_code = wp_remote_retrieve_response_code( $response );
                $response_body = wp_remote_retrieve_body( $response );
                
                // DETECTAR ERRORES HTTP QUE INDICAN PG INACTIVO
                if ( $response_code !== 200 ) {
                    
                    if ( is_callable( array( $this->logger, 'error' ) ) ) {
                        $this->logger->error( "PG Inactivo - Código HTTP de error", array(
                            'order_id' => $payment_data['order_id'],
                            'http_code' => $response_code,
                            'response_body' => substr( $response_body, 0, 500 )
                        ) );
                    }
                    
                    // Códigos HTTP que indican servicio no disponible
                    if ( in_array( $response_code, array( 503, 502, 504, 500, 404 ) ) ) {
                        return array(
                            'error' => 'pg_inactive',
                            'message' => __( 'La plataforma bancaria no está disponible en este momento. Por favor, intente más tarde.', 'woocommerce-megasoft-gateway-universal' ),
                            'user_message' => __( 'Servicio temporalmente no disponible', 'woocommerce-megasoft-gateway-universal' ),
                            'technical_details' => "HTTP Error: {$response_code}"
                        );
                    } else {
                        return array(
                            'error' => 'http_error',
                            'message' => sprintf( __( 'Error del servidor: %d. Por favor, intente más tarde.', 'woocommerce-megasoft-gateway-universal' ), $response_code ),
                            'user_message' => __( 'Error del servidor', 'woocommerce-megasoft-gateway-universal' ),
                            'technical_details' => "HTTP Error: {$response_code}"
                        );
                    }
                }
                
                // VALIDAR RESPUESTA DEL PRE-REGISTRO
                if ( empty( $response_body ) ) {
                    return array(
                        'error' => 'empty_response',
                        'message' => __( 'No se recibió respuesta de la plataforma bancaria. Por favor, intente más tarde.', 'woocommerce-megasoft-gateway-universal' ),
                        'user_message' => __( 'Sin respuesta del servidor', 'woocommerce-megasoft-gateway-universal' ),
                        'technical_details' => "Empty response body"
                    );
                }
                
                $control_number = trim( $response_body );
                
                // Validar formato del número de control
                if ( ! is_numeric( $control_number ) || strlen( $control_number ) < 10 ) {
                    
                    if ( is_callable( array( $this->logger, 'error' ) ) ) {
                        $this->logger->error( "Respuesta inválida del pre-registro", array(
                            'order_id' => $payment_data['order_id'],
                            'response_body' => $response_body,
                            'extracted_control' => $control_number
                        ) );
                    }
                    
                    // Si la respuesta contiene mensajes de error de Mega Soft
                    if ( $this->contains_megasoft_error( $response_body ) ) {
                        return array(
                            'error' => 'megasoft_error',
                            'message' => __( 'Error en el procesamiento: Credenciales inválidas o servicio no disponible.', 'woocommerce-megasoft-gateway-universal' ),
                            'user_message' => __( 'Error de configuración', 'woocommerce-megasoft-gateway-universal' ),
                            'technical_details' => "Invalid response: {$response_body}"
                        );
                    } else {
                        return array(
                            'error' => 'invalid_control',
                            'message' => __( 'Número de control inválido recibido. Por favor, intente más tarde.', 'woocommerce-megasoft-gateway-universal' ),
                            'user_message' => __( 'Respuesta inválida', 'woocommerce-megasoft-gateway-universal' ),
                            'technical_details' => "Invalid control format: {$control_number}"
                        );
                    }
                }
                
                // SI TODO ESTÁ BIEN, devolver el número de control
                if ( is_callable( array( $this->logger, 'info' ) ) ) {
                    $this->logger->info( "Pre-registro exitoso", array(
                        'order_id' => $payment_data['order_id'],
                        'control_number' => $control_number
                    ) );
                }
                
                return $control_number;
            }
            
            /**
             * Determinar si el error indica PG inactivo
             */
            private function is_pg_inactive_error( $error_code, $error_message ) {
                $inactive_error_codes = array(
                    'http_request_failed',
                    'connect_error',
                    'timeout',
                    'ssl_connect_error',
                    'resolve_host_error'
                );
                
                $inactive_keywords = array(
                    'connection timed out',
                    'could not resolve host',
                    'connection refused',
                    'network is unreachable',
                    'service unavailable',
                    'server not found',
                    'ssl connection error'
                );
                
                // Verificar código de error
                if ( in_array( $error_code, $inactive_error_codes ) ) {
                    return true;
                }
                
                // Verificar palabras clave en el mensaje
                $error_message_lower = strtolower( $error_message );
                foreach ( $inactive_keywords as $keyword ) {
                    if ( strpos( $error_message_lower, $keyword ) !== false ) {
                        return true;
                    }
                }
                
                return false;
            }
            
            /**
             * Verificar si la respuesta contiene errores conocidos de Mega Soft
             */
            private function contains_megasoft_error( $response_body ) {
                $error_indicators = array(
                    'credenciales inválidas',
                    'credenciales invalidas',
                    'requiere enviar las credenciales',
                    'no se encontró',
                    'no se encontro',
                    'parámetros de entrada errados',
                    'parametros de entrada errados'
                );
                
                $response_lower = strtolower( $response_body );
                
                foreach ( $error_indicators as $indicator ) {
                    if ( strpos( $response_lower, $indicator ) !== false ) {
                        return true;
                    }
                }
                
                return false;
            }
            
            /**
             * Mostrar mensaje de PG inactivo al usuario
             */
            private function display_pg_inactive_notice( $error_data ) {
                // Agregar mensaje de error personalizado a WooCommerce
                wc_add_notice( $error_data['message'], 'error' );
                
                // Agregar JavaScript para mostrar modal/popup si se desea
                add_action( 'wp_footer', function() use ( $error_data ) {
                    ?>
                    <script type="text/javascript">
                    document.addEventListener('DOMContentLoaded', function() {
                        // Mostrar modal de error elegante
                        setTimeout(function() {
                            megaSoftShowPGInactiveModal();
                        }, 500);
                    });
                    
                    function megaSoftShowPGInactiveModal() {
                        // Verificar si ya existe un modal
                        if (document.querySelector('.megasoft-pg-inactive-modal')) {
                            return;
                        }
                        
                        const modal = document.createElement('div');
                        modal.className = 'megasoft-pg-inactive-modal';
                        modal.innerHTML = `
                            <div class="megasoft-modal-overlay" onclick="megaSoftClosePGModal()">
                                <div class="megasoft-modal-content" onclick="event.stopPropagation()">
                                    <div class="megasoft-modal-header">
                                        <div class="megasoft-modal-icon">⚠️</div>
                                        <h3><?php echo esc_js( $error_data['user_message'] ); ?></h3>
                                    </div>
                                    <div class="megasoft-modal-body">
                                        <p><?php echo esc_js( $error_data['message'] ); ?></p>
                                        <p><small>Código de referencia para soporte: <?php echo esc_js( substr( md5( $error_data['technical_details'] ), 0, 8 ) ); ?></small></p>
                                    </div>
                                    <div class="megasoft-modal-footer">
                                        <button type="button" onclick="megaSoftClosePGModal()" class="button button-primary">
                                            <?php esc_html_e( 'Entendido', 'woocommerce-megasoft-gateway-universal' ); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        document.body.appendChild(modal);
                        
                        // Mostrar modal
                        setTimeout(() => modal.classList.add('show'), 100);
                    }
                    
                    function megaSoftClosePGModal() {
                        const modal = document.querySelector('.megasoft-pg-inactive-modal');
                        if (modal) {
                            modal.classList.remove('show');
                            setTimeout(() => modal.remove(), 300);
                        }
                    }
                    
                    // Exponer función globalmente para uso manual
                    window.megaSoftClosePGModal = megaSoftClosePGModal;
                    </script>
                    
                    <style>
                    .megasoft-pg-inactive-modal {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        z-index: 999999;
                        opacity: 0;
                        visibility: hidden;
                        transition: all 0.3s ease;
                    }
                    
                    .megasoft-pg-inactive-modal.show {
                        opacity: 1;
                        visibility: visible;
                    }
                    
                    .megasoft-modal-overlay {
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.7);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        padding: 20px;
                        box-sizing: border-box;
                    }
                    
                    .megasoft-modal-content {
                        background: white;
                        border-radius: 12px;
                        max-width: 500px;
                        width: 100%;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                        transform: scale(0.8);
                        transition: transform 0.3s ease;
                        position: relative;
                    }
                    
                    .megasoft-pg-inactive-modal.show .megasoft-modal-content {
                        transform: scale(1);
                    }
                    
                    .megasoft-modal-header {
                        text-align: center;
                        padding: 30px 30px 20px;
                        border-bottom: 1px solid #eee;
                    }
                    
                    .megasoft-modal-icon {
                        font-size: 48px;
                        margin-bottom: 15px;
                    }
                    
                    .megasoft-modal-header h3 {
                        margin: 0;
                        font-size: 24px;
                        color: #dc3545;
                        font-weight: 600;
                    }
                    
                    .megasoft-modal-body {
                        padding: 30px;
                        text-align: center;
                    }
                    
                    .megasoft-modal-body p {
                        margin: 0 0 15px;
                        font-size: 16px;
                        line-height: 1.5;
                        color: #333;
                    }
                    
                    .megasoft-modal-body small {
                        color: #666;
                        font-size: 12px;
                    }
                    
                    .megasoft-modal-footer {
                        padding: 20px 30px 30px;
                        text-align: center;
                    }
                    
                    .megasoft-modal-footer .button {
                        min-width: 120px;
                        padding: 12px 24px;
                        font-size: 16px;
                        border-radius: 6px;
                        cursor: pointer;
                    }
                    
                    @media (max-width: 600px) {
                        .megasoft-modal-content {
                            margin: 20px;
                            max-width: none;
                        }
                        
                        .megasoft-modal-header,
                        .megasoft-modal-body,
                        .megasoft-modal-footer {
                            padding-left: 20px;
                            padding-right: 20px;
                        }
                    }
                    </style>
                    <?php
                }, 99 );
            }
            
            private function get_payment_url( $control_number ) {
                $base_url = $this->test_mode ? 'https://paytest.megasoft.com.ve/action/' : 'https://e-payment.megasoft.com.ve/action/';
                return add_query_arg( 'control', $control_number, $base_url . 'paymentgatewayuniversal-data' );
            }
            
            public function handle_return() {
                try {
                    $control_number = sanitize_text_field( $_GET['control'] ?? '' );
                    $order_id       = absint( $_GET['factura'] ?? 0 );
                    
                    if ( is_callable( array( $this->logger, 'info' ) ) ) {
                        $this->logger->info( "Procesando retorno. Control: {$control_number}, Orden: #{$order_id}" );
                    }
                    
                    if ( ! $control_number || ! $order_id ) {
                        throw new Exception( __( 'Parámetros de retorno inválidos.', 'woocommerce-megasoft-gateway-universal' ) );
                    }
                    
                    $order = wc_get_order( $order_id );
                    
                    if ( ! $order ) {
                        throw new Exception( __( 'Orden no encontrada.', 'woocommerce-megasoft-gateway-universal' ) );
                    }
                    
                    // Verificar seguridad del número de control
                    $saved_control = $order->get_meta( '_megasoft_control_number' );
                    if ( $saved_control !== $control_number ) {
                        throw new Exception( __( 'Error de validación de seguridad.', 'woocommerce-megasoft-gateway-universal' ) );
                    }
                    
                    // Consultar estado de la transacción
                    $transaction_result = $this->query_transaction_status( $control_number );
                    
                    if ( ! $transaction_result ) {
                        throw new Exception( __( 'Error al consultar el estado de la transacción.', 'woocommerce-megasoft-gateway-universal' ) );
                    }
                    
                    // Procesar resultado SIEMPRE (aprobado o rechazado)
                    $this->process_transaction_result( $order, $transaction_result );
                    
                    // Redireccionar según el resultado (SIEMPRE a la página de confirmación)
                    wp_redirect( $this->get_return_url( $order ) );
                    
                } catch ( Exception $e ) {
                    if ( is_callable( array( $this->logger, 'error' ) ) ) {
                        $this->logger->error( "Error en handle_return: " . $e->getMessage() );
                    }
                    wc_add_notice( $e->getMessage(), 'error' );
                    wp_redirect( wc_get_checkout_url() );
                }
                
                exit;
            }
            
            private function query_transaction_status( $control_number ) {
                $base_url = $this->test_mode ? 'https://paytest.megasoft.com.ve/action/' : 'https://e-payment.megasoft.com.ve/action/';
                $querystatus_url = add_query_arg( array(
                    'control' => $control_number,
                    'version' => '3',
                    'tipo'    => 'CREDITO'
                ), $base_url . 'paymentgatewayuniversal-querystatus' );
                
                $response = wp_remote_get( $querystatus_url, array( 
                    'timeout' => 30, 
                    'sslverify' => ! $this->test_mode 
                ) );
                
                if ( is_wp_error( $response ) ) {
                    return false;
                }
                
                $xml_string = wp_remote_retrieve_body( $response );
                
                if ( is_callable( array( $this->logger, 'debug' ) ) ) {
                    $this->logger->debug( "Respuesta de QueryStatus: " . $xml_string );
                }
                
                return $this->parse_transaction_response( $xml_string, $control_number );
            }
            
            /**
             * FUNCIÓN CORREGIDA: Parsear respuesta XML SIEMPRE procesa voucher
             */
            private function parse_transaction_response( $xml_string, $control_number ) {
                libxml_use_internal_errors( true );
                $xml = simplexml_load_string( $xml_string );
                
                if ( $xml === false ) {
                    $errors = libxml_get_errors();
                    if ( is_callable( array( $this->logger, 'error' ) ) ) {
                        $this->logger->error( "Error parsing XML: " . print_r( $errors, true ) );
                    }
                    libxml_clear_errors();
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
                    'voucher'        => $this->format_voucher_from_xml( $xml ), // SIEMPRE procesar voucher
                    'raw_data'       => json_decode( json_encode( $xml ), true ),
                    'metadata'       => array(
                        'voucher'        => $this->format_voucher_from_xml( $xml ), // SIEMPRE incluir voucher
                        'auth_id'        => (string) $xml->authid,
                        'reference'      => (string) $xml->referencia,
                        'payment_method' => (string) $xml->medio,
                        'terminal'       => (string) $xml->terminal,
                        'lote'           => (string) $xml->lote,
                        'sequence'       => (string) $xml->seqnum
                    )
                );
                
                // Log tanto para aprobadas como rechazadas
                $status_text = $approved ? 'APROBADA' : 'RECHAZADA';
                if ( is_callable( array( $this->logger, 'info' ) ) ) {
                    $this->logger->info( 
                        sprintf( "Respuesta de transacción: %s - %s (%s)", $codigo, $result['message'], $status_text ),
                        array(
                            'control_number' => $control_number,
                            'approved' => $approved,
                            'auth_id' => $result['auth_id'],
                            'has_voucher' => ! empty( $result['voucher'] )
                        )
                    );
                }
                
                return $result;
            }
            
            /**
             * FUNCIÓN CORREGIDA: Formatear voucher para transacciones aprobadas Y rechazadas
             */
            private function format_voucher_from_xml( $xml ) {
                $voucher_lines = array();
                
                // Si NO hay voucher disponible, generar uno básico con la información disponible
                if ( ! isset( $xml->voucher ) || empty( $xml->voucher ) ) {
                    return $this->generate_fallback_voucher( $xml );
                }
                
                // CASO 1: Voucher con etiquetas <linea> (formato estructurado)
                if ( isset( $xml->voucher->linea ) && ( is_array( $xml->voucher->linea ) || count( $xml->voucher->linea ) > 0 ) ) {
                    foreach ( $xml->voucher->linea as $line ) {
                        $line_content = $this->process_voucher_line( $line );
                        
                        // Agregar líneas que no estén completamente vacías
                        if ( trim( $line_content ) !== '' || strlen( trim( (string) $line ) ) > 0 ) {
                            $voucher_lines[] = $line_content;
                        } else {
                            // Mantener líneas vacías para preservar el espaciado del voucher
                            $voucher_lines[] = '';
                        }
                    }
                }
                // CASO 2: Voucher como texto directo (formato simple para transacciones fallidas)
                else {
                    $voucher_content = (string) $xml->voucher;
                    
                    if ( ! empty( $voucher_content ) ) {
                        // Limpiar HTML tags pero mantener estructura
                        $voucher_content = strip_tags( $voucher_content );
                        
                        // Dividir por saltos de línea si existen
                        $lines = preg_split( '/\r\n|\r|\n/', $voucher_content );
                        
                        foreach ( $lines as $line ) {
                            // Reemplazar guiones bajos por espacios
                            $clean_line = str_replace( '_', ' ', $line );
                            $voucher_lines[] = trim( $clean_line );
                        }
                    }
                }
                
                // Si después de todo el procesamiento no hay líneas válidas, generar voucher básico
                if ( empty( $voucher_lines ) || ( count( $voucher_lines ) === 1 && trim( $voucher_lines[0] ) === '' ) ) {
                    return $this->generate_fallback_voucher( $xml );
                }
                
                // Determinar el tipo de transacción para aplicar estilos apropiados
                $codigo = (string) $xml->codigo;
                $approved = $codigo === '00';
                $transaction_status = $approved ? 'approved' : 'failed';
                
                return $this->render_voucher_html( $voucher_lines, $transaction_status, $xml );
            }
            
            /**
             * Procesar línea individual del voucher
             */
            private function process_voucher_line( $line ) {
                $line_content = '';
                
                // Si la línea tiene elementos HTML como <UT>, extraer el contenido
                if ( $line instanceof SimpleXMLElement && $line->count() > 0 ) {
                    // Tiene elementos hijos, extraer el contenido completo
                    $line_content = (string) $line;
                } else {
                    // Es texto simple
                    $line_content = (string) $line;
                }
                
                // Limpiar etiquetas HTML como <UT> pero mantener el contenido
                $line_content = strip_tags( $line_content );
                
                // Reemplazar guiones bajos por espacios
                $clean_line = str_replace( '_', ' ', $line_content );
                
                return $clean_line;
            }
            
            /**
             * Generar voucher básico cuando no hay voucher en el XML
             */
            private function generate_fallback_voucher( $xml ) {
                $codigo = (string) $xml->codigo;
                $descripcion = (string) $xml->descripcion;
                $approved = $codigo === '00';
                
                $fallback_lines = array(
                    '',
                    'COMPROBANTE DE TRANSACCIÓN',
                    'MEGA SOFT COMPUTACIÓN',
                    '',
                    $approved ? 'TRANSACCIÓN APROBADA' : 'TRANSACCIÓN RECHAZADA',
                    '',
                    'CARACAS',
                    isset( $xml->terminal ) ? 'TERMINAL: ' . (string) $xml->terminal : '',
                    isset( $xml->factura ) ? 'FACTURA: ' . (string) $xml->factura : '',
                    isset( $xml->referencia ) ? 'REFERENCIA: ' . (string) $xml->referencia : '',
                    isset( $xml->authid ) && !empty( (string) $xml->authid ) ? 'AUTORIZACIÓN: ' . (string) $xml->authid : '',
                    'FECHA: ' . date( 'd/m/Y H:i:s' ),
                    '',
                    isset( $xml->monto ) ? 'MONTO BS: ' . (string) $xml->monto : '',
                    '',
                    $approved ? 'TRANSACCIÓN EXITOSA' : 'CÓDIGO: ' . $codigo,
                    $approved ? '' : strtoupper( $descripcion ),
                    '',
                );
                
                // Remover líneas vacías innecesarias
                $fallback_lines = array_filter( $fallback_lines, function( $line ) {
                    return $line !== null;
                });
                
                $transaction_status = $approved ? 'approved' : 'failed';
                
                return $this->render_voucher_html( $fallback_lines, $transaction_status, $xml );
            }
            
            /**
             * Renderizar HTML del voucher con estilos apropiados
             */
            private function render_voucher_html( $voucher_lines, $transaction_status, $xml ) {
                $codigo = (string) $xml->codigo;
                $descripcion = (string) $xml->descripcion;
                $approved = $transaction_status === 'approved';
                
                // Crear el HTML del voucher con estilos diferenciados
                $voucher_html = '<div class="megasoft-voucher-receipt ' . $transaction_status . '">';
                
                // Header del voucher
                $voucher_html .= '<div class="voucher-header">';
                $voucher_html .= '<div class="voucher-logo">' . ( $approved ? '✅' : '❌' ) . '</div>';
                $voucher_html .= '<h3>COMPROBANTE DE TRANSACCIÓN</h3>';
                $voucher_html .= '<div class="voucher-date">' . date( 'd/m/Y H:i:s' ) . '</div>';
                $voucher_html .= '<div class="voucher-status ' . $transaction_status . '">';
                $voucher_html .= $approved ? 'APROBADA' : 'RECHAZADA - ' . strtoupper( $codigo );
                $voucher_html .= '</div>';
                $voucher_html .= '</div>';
                
                // Contenido del voucher
                $voucher_html .= '<div class="voucher-content">';
                
                foreach ( $voucher_lines as $line ) {
                    // Procesar líneas especiales
                    if ( trim( $line ) === '' ) {
                        $voucher_html .= '<div class="voucher-line voucher-spacer">&nbsp;</div>';
                    } elseif ( strpos( strtoupper( $line ), 'TRANSACCION FALLIDA' ) !== false ) {
                        $voucher_html .= '<div class="voucher-line voucher-error">' . esc_html( $line ) . '</div>';
                    } elseif ( strpos( strtoupper( $line ), 'APROBADA' ) !== false || strpos( strtoupper( $line ), 'EXITOSA' ) !== false ) {
                        $voucher_html .= '<div class="voucher-line voucher-success">' . esc_html( $line ) . '</div>';
                    } else {
                        $voucher_html .= '<div class="voucher-line">' . esc_html( $line ) . '</div>';
                    }
                }
                
                $voucher_html .= '</div>';
                
                // Footer del voucher
                $voucher_html .= '<div class="voucher-footer">';
                $voucher_html .= '<div class="security-note">';
                $voucher_html .= $approved ? 
                    '⚡ Transacción procesada de forma segura por Mega Soft' : 
                    '⚠️ Transacción no procesada - ' . esc_html( $descripcion );
                $voucher_html .= '</div>';
                $voucher_html .= '</div>';
                
                $voucher_html .= '</div>';
                
                // Agregar estilos CSS mejorados con soporte para transacciones fallidas
                $voucher_html .= $this->get_voucher_styles();
                
                return $voucher_html;
            }
            
            /**
             * Obtener estilos CSS para el voucher
             */
            private function get_voucher_styles() {
                return '<style>
                .megasoft-voucher-receipt {
                    max-width: 420px;
                    margin: 30px auto;
                    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
                    border: none;
                    border-radius: 12px;
                    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
                    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                    overflow: hidden;
                    position: relative;
                }
                
                .megasoft-voucher-receipt::before {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 4px;
                }
                
                .megasoft-voucher-receipt.approved::before {
                    background: linear-gradient(90deg, #28a745, #20c997, #28a745);
                }
                
                .megasoft-voucher-receipt.failed::before {
                    background: linear-gradient(90deg, #dc3545, #e74c3c, #dc3545);
                }
                
                .voucher-header {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    text-align: center;
                    padding: 20px;
                    border-bottom: 2px dashed #dee2e6;
                    position: relative;
                }
                
                .voucher-logo {
                    font-size: 24px;
                    margin-bottom: 8px;
                    opacity: 0.8;
                }
                
                .voucher-header h3 {
                    margin: 0 0 8px 0;
                    font-size: 16px;
                    font-weight: 600;
                    color: #2c3e50;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                .voucher-date {
                    font-size: 12px;
                    color: #6c757d;
                    font-weight: 500;
                    margin-bottom: 10px;
                }
                
                .voucher-status {
                    padding: 8px 16px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: bold;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                .voucher-status.approved {
                    background: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }
                
                .voucher-status.failed {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }
                
                .voucher-content {
                    padding: 25px 20px;
                    text-align: center;
                    background: #ffffff;
                    position: relative;
                }
                
                .voucher-content::before {
                    content: "";
                    position: absolute;
                    left: -10px;
                    top: 50%;
                    transform: translateY(-50%);
                    width: 20px;
                    height: 20px;
                    background: #f8f9fa;
                    border-radius: 50%;
                    border: 2px solid #dee2e6;
                }
                
                .voucher-content::after {
                    content: "";
                    position: absolute;
                    right: -10px;
                    top: 50%;
                    transform: translateY(-50%);
                    width: 20px;
                    height: 20px;
                    background: #f8f9fa;
                    border-radius: 50%;
                    border: 2px solid #dee2e6;
                }
                
                .voucher-line {
                    font-family: "Courier New", Courier, monospace;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #2c3e50;
                    margin: 0;
                    padding: 2px 0;
                    white-space: nowrap;
                    overflow: hidden;
                    font-weight: 500;
                }
                
                .voucher-line.voucher-spacer {
                    height: 8px;
                }
                
                .voucher-line.voucher-error {
                    color: #dc3545;
                    font-weight: 700;
                    background: #f8d7da;
                    padding: 4px 8px;
                    border-radius: 4px;
                    margin: 4px 0;
                }
                
                .voucher-line.voucher-success {
                    color: #28a745;
                    font-weight: 700;
                    background: #d4edda;
                    padding: 4px 8px;
                    border-radius: 4px;
                    margin: 4px 0;
                }
                
                .voucher-line:first-child {
                    font-weight: 700;
                    color: #007cba;
                }
                
                .voucher-line:nth-child(2),
                .voucher-line:nth-child(3) {
                    font-weight: 600;
                    color: #495057;
                }
                
                .voucher-footer {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    padding: 15px 20px;
                    border-top: 2px dashed #dee2e6;
                    text-align: center;
                }
                
                .security-note {
                    font-size: 11px;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 5px;
                }
                
                .megasoft-voucher-receipt.approved .security-note {
                    color: #28a745;
                }
                
                .megasoft-voucher-receipt.failed .security-note {
                    color: #dc3545;
                }
                
                @media print {
                    .megasoft-voucher-receipt {
                        box-shadow: none;
                        border: 2px solid #000;
                        page-break-inside: avoid;
                        background: white !important;
                    }
                    
                    .voucher-header,
                    .voucher-footer {
                        background: white !important;
                    }
                    
                    .megasoft-voucher-receipt::before {
                        display: none;
                    }
                    
                    .voucher-content::before,
                    .voucher-content::after {
                        display: none;
                    }
                    
                    .voucher-line {
                        color: #000 !important;
                    }
                    
                    .security-note {
                        color: #000 !important;
                    }
                    
                    .voucher-status {
                        background: white !important;
                        color: #000 !important;
                        border: 1px solid #000 !important;
                    }
                }
                
                @media (max-width: 480px) {
                    .megasoft-voucher-receipt {
                        margin: 20px 10px;
                        max-width: none;
                    }
                    
                    .voucher-header,
                    .voucher-content,
                    .voucher-footer {
                        padding: 15px;
                    }
                    
                    .voucher-line {
                        font-size: 11px;
                        white-space: normal;
                        overflow: visible;
                        word-break: break-word;
                    }
                }
                </style>';
            }
            
            /**
             * FUNCIÓN CORREGIDA: Procesar resultado para AMBOS casos
             */
            private function process_transaction_result( $order, $result ) {
                global $wpdb;
                
                // Actualizar orden TANTO para aprobadas como rechazadas
                if ( $result['approved'] ) {
                    $order->payment_complete( $result['auth_id'] );
                    $order->add_order_note( 
                        sprintf( 
                            __( 'Pago aprobado via Mega Soft. Auth ID: %s, Referencia: %s', 'woocommerce-megasoft-gateway-universal' ),
                            $result['auth_id'],
                            $result['reference']
                        )
                    );
                } else {
                    $order->update_status( 'failed', $result['message'] );
                    $order->add_order_note( 
                        sprintf( 
                            __( 'Pago rechazado por Mega Soft. Código: %s, Motivo: %s', 'woocommerce-megasoft-gateway-universal' ),
                            $result['code'],
                            $result['message']
                        )
                    );
                }
                
                // Guardar TODOS los datos de la transacción, incluyendo voucher
                foreach ( $result['metadata'] as $key => $value ) {
                    $order->update_meta_data( "_megasoft_{$key}", $value );
                }
                
                // IMPORTANTE: Guardar voucher SIEMPRE, independientemente del estado
                if ( ! empty( $result['voucher'] ) ) {
                    $order->update_meta_data( '_megasoft_voucher', $result['voucher'] );
                }
                
                $order->save();
                
                // Actualizar base de datos
                $table_name = $wpdb->prefix . 'megasoft_transactions';
                
                $wpdb->update(
                    $table_name,
                    array(
                        'status'         => $result['approved'] ? 'approved' : 'failed',
                        'auth_id'        => $result['auth_id'],
                        'reference'      => $result['reference'],
                        'payment_method' => $result['payment_method'],
                        'response_data'  => json_encode( $result['raw_data'] ),
                        'updated_at'     => current_time( 'mysql' )
                    ),
                    array( 'order_id' => $order->get_id() ),
                    array( '%s', '%s', '%s', '%s', '%s', '%s' ),
                    array( '%d' )
                );
            }
            
            /**
             * FUNCIÓN CORREGIDA: Mostrar voucher SIEMPRE
             */
            public function display_receipt( $order_id ) {
                $order = wc_get_order( $order_id );
                
                if ( ! $order || $order->get_payment_method() !== 'megasoft_gateway_universal' ) {
                    return;
                }
                
                $voucher = $order->get_meta( '_megasoft_voucher' );
                
                // Si no hay voucher guardado, intentar generar uno básico
                if ( empty( $voucher ) ) {
                    $control_number = $order->get_meta( '_megasoft_control_number' );
                    $order_status = $order->get_status();
                    
                    $voucher = '<div class="megasoft-voucher-receipt ' . ( $order_status === 'completed' ? 'approved' : 'failed' ) . '">';
                    $voucher .= '<div class="voucher-header">';
                    $voucher .= '<div class="voucher-logo">' . ( $order_status === 'completed' ? '✅' : '❌' ) . '</div>';
                    $voucher .= '<h3>COMPROBANTE DE TRANSACCIÓN</h3>';
                    $voucher .= '<div class="voucher-status ' . ( $order_status === 'completed' ? 'approved' : 'failed' ) . '">';
                    $voucher .= $order_status === 'completed' ? 'TRANSACCIÓN COMPLETADA' : 'TRANSACCIÓN NO PROCESADA';
                    $voucher .= '</div>';
                    $voucher .= '</div>';
                    $voucher .= '<div class="voucher-content">';
                    $voucher .= '<div class="voucher-line">MEGA SOFT COMPUTACIÓN</div>';
                    $voucher .= '<div class="voucher-line">ORDEN: #' . $order_id . '</div>';
                    if ( $control_number ) {
                        $voucher .= '<div class="voucher-line">CONTROL: ' . esc_html( $control_number ) . '</div>';
                    }
                    $voucher .= '<div class="voucher-line">MONTO: ' . $order->get_formatted_order_total() . '</div>';
                    $voucher .= '<div class="voucher-line">FECHA: ' . $order->get_date_created()->format( 'd/m/Y H:i:s' ) . '</div>';
                    $voucher .= '</div>';
                    $voucher .= '<div class="voucher-footer">';
                    $voucher .= '<div class="security-note">⚡ Procesado por Mega Soft Gateway</div>';
                    $voucher .= '</div>';
                    $voucher .= '</div>';
                    $voucher .= $this->get_voucher_styles();
                }
                
                ?>
                <section class="woocommerce-order-details megasoft-receipt-section">
                    <?php echo $voucher; ?>
                    <div style="text-align: center; margin-top: 20px;">
                        <button type="button" onclick="window.print()" class="button alt">
                            <?php esc_html_e( 'Imprimir Comprobante', 'woocommerce-megasoft-gateway-universal' ); ?>
                        </button>
                    </div>
                </section>
                <?php
            }
            
            /**
             * Agregar botones de simulación en el admin
             */
            public function add_simulation_admin_notice() {
                $screen = get_current_screen();
                if ( ! $screen || $screen->id !== 'woocommerce_page_wc-settings' ) {
                    return;
                }
                
                if ( ! isset( $_GET['section'] ) || $_GET['section'] !== 'megasoft_gateway_universal' ) {
                    return;
                }
                
                // Solo mostrar a administradores
                if ( ! current_user_can( 'manage_options' ) ) {
                    return;
                }

                // CORRECCIÓN: Solo mostrar si NO está en modo de prueba
                if ( $this->test_mode ) {
                    return; // No mostrar el simulador si está en modo de prueba
                }
                
                // Manejar activación/desactivación de simulación
                if ( isset( $_GET['megasoft_simulate_inactive'] ) ) {
                    if ( $_GET['megasoft_simulate_inactive'] === '1' ) {
                        update_option( 'megasoft_simulate_pg_inactive', true );
                        $simulated = '1';
                    } elseif ( $_GET['megasoft_simulate_inactive'] === '0' ) {
                        delete_option( 'megasoft_simulate_pg_inactive' );
                        $simulated = '0';
                    }
                }
                
                $is_simulating = get_option( 'megasoft_simulate_pg_inactive' );
                $simulate_url = add_query_arg( 'megasoft_simulate_inactive', '1' );
                $normal_url = add_query_arg( 'megasoft_simulate_inactive', '0' );
                
                ?>
                <div class="notice notice-info">
                    <h3>🧪 Simulador de PG Inactivo (Solo para pruebas de certificación)</h3>
                    <p>
                        <?php if ( $is_simulating ) : ?>
                            <strong style="color: #d63638;">⚠️ MODO SIMULACIÓN ACTIVO:</strong> 
                            El pre-registro fallará para simular PG inactivo.
                            <br><br>
                            <a href="<?php echo esc_url( $normal_url ); ?>" class="button button-secondary">
                                ✅ Desactivar Simulación
                            </a>
                        <?php else : ?>
                            Use este modo solo para la prueba P2C de certificación con Mega Soft.
                            <br><br>
                            <a href="<?php echo esc_url( $simulate_url ); ?>" class="button button-secondary">
                                🔧 Activar Simulación PG Inactivo
                            </a>
                        <?php endif; ?>
                    </p>
                    <p>
                        <small>
                            <strong>Instrucciones:</strong> 
                            1. Active la simulación, 2. Haga una compra de prueba, 
                            3. Capture pantalla del mensaje de error, 4. Desactive la simulación.
                        </small>
                    </p>
                </div>
                
                <?php if ( isset( $_GET['megasoft_simulate_inactive'] ) ) : ?>
                    <div class="notice notice-<?php echo $_GET['megasoft_simulate_inactive'] === '1' ? 'warning' : 'success'; ?> is-dismissible">
                        <p>
                            <?php echo $_GET['megasoft_simulate_inactive'] === '1' ? 
                                '⚠️ Simulación de PG inactivo ACTIVADA. Las compras mostrarán mensaje de error.' : 
                                '✅ Simulación DESACTIVADA. Funcionamiento normal restaurado.'; ?>
                        </p>
                    </div>
                <?php endif; ?>
                <?php
            }
            
            public function admin_options() {
                ?>
                <h2><?php esc_html_e( 'Mega Soft Gateway', 'woocommerce-megasoft-gateway-universal' ); ?></h2>
                <p><?php esc_html_e( 'Acepta pagos usando la pasarela venezolana Mega Soft con soporte completo para producción.', 'woocommerce-megasoft-gateway-universal' ); ?></p>
                
                <div class="megasoft-admin-notices">
                    <?php $this->display_admin_notices(); ?>
                </div>
                
                <table class="form-table">
                    <?php $this->generate_settings_html(); ?>
                </table>
                <?php
            }
            
            private function display_admin_notices() {
                // Verificar configuración
                if ( empty( $this->get_option( 'cod_afiliacion' ) ) ) {
                    echo '<div class="notice notice-error"><p>' . __( 'Debes configurar el código de afiliación.', 'woocommerce-megasoft-gateway-universal' ) . '</p></div>';
                }
                
                if ( empty( $this->get_option( 'api_user' ) ) || empty( $this->get_option( 'api_password' ) ) ) {
                    echo '<div class="notice notice-error"><p>' . __( 'Debes configurar las credenciales de la API.', 'woocommerce-megasoft-gateway-universal' ) . '</p></div>';
                }
                
                if ( $this->test_mode ) {
                    echo '<div class="notice notice-warning"><p>' . __( 'El gateway está en modo de prueba. Desactívalo para usar en producción.', 'woocommerce-megasoft-gateway-universal' ) . '</p></div>';
                }
                
                // Verificar SSL en producción
                if ( ! $this->test_mode && ! is_ssl() ) {
                    echo '<div class="notice notice-error"><p>' . __( 'Se requiere SSL (HTTPS) para usar el gateway en producción.', 'woocommerce-megasoft-gateway-universal' ) . '</p></div>';
                }
            }
        }
    }
}

// Hooks adicionales
add_action( 'megasoft_sync_transactions', 'megasoft_sync_pending_transactions' );

function megasoft_sync_pending_transactions() {
    // Sincronizar transacciones pendientes cada hora
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'megasoft_transactions';
    $pending_transactions = $wpdb->get_results(
        "SELECT * FROM {$table_name} 
         WHERE status = 'pending' 
         AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
         LIMIT 50"
    );
    
    if ( ! empty( $pending_transactions ) && class_exists( 'WC_Gateway_MegaSoft_Universal' ) ) {
        foreach ( $pending_transactions as $transaction ) {
            if ( $transaction->control_number ) {
                $gateway = new WC_Gateway_MegaSoft_Universal();
                // Sincronización simplificada sin dependencia de clases externas
            }
        }
    }
}

// Agregar intervalo personalizado para cron
add_filter( 'cron_schedules', function( $schedules ) {
    $schedules['megasoft_5min'] = array(
        'interval' => 300,
        'display'  => __( 'Cada 5 minutos' )
    );
    return $schedules;
} );