<?php
/**
 * Plugin Name:         Pasarela de Pago Mega Soft para WooCommerce (Modalidad NO UNIVERSAL) - PRODUCCIÓN v2
 * Plugin URI:          https://github.com/
 * Description:         Pasarela de pago venezolana Mega Soft v4.24 - Modalidad NO UNIVERSAL con captura directa de tarjetas, REST API v2, múltiples métodos de pago (Tarjetas, Pago Móvil, Criptomonedas, Banplus Pay, Zelle). PCI-DSS Compliant.
 * Author:              Mega Soft Integration Team
 * Author URI:          https://megasoft.com.ve
 * Version:             4.0.0
 * Requires at least:   5.8
 * Requires PHP:        7.4
 * WC requires at least: 6.0
 * WC tested up to:     8.5
 * Text Domain:         woocommerce-megasoft-gateway-v2
 * Domain Path:         /languages
 * Network:             false
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 * @author Mega Soft Computación C.A.
 */

// Prevenir acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Definir constantes del plugin
define( 'MEGASOFT_V2_VERSION', '4.0.0' );
define( 'MEGASOFT_V2_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MEGASOFT_V2_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MEGASOFT_V2_PLUGIN_FILE', __FILE__ );
define( 'MEGASOFT_V2_API_VERSION', 'v2' );
define( 'MEGASOFT_V2_DOC_VERSION', '4.24' );

/**
 * Verificar si WooCommerce está activo
 */
function megasoft_v2_check_woocommerce() {
    return class_exists( 'WooCommerce' );
}

/**
 * Verificar requisitos del sistema
 */
function megasoft_v2_check_requirements() {
    $errors = array();

    // Verificar WooCommerce
    if ( ! megasoft_v2_check_woocommerce() ) {
        $errors[] = __( 'WooCommerce debe estar instalado y activo.', 'woocommerce-megasoft-gateway-v2' );
    }

    // Verificar versión de PHP
    if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
        $errors[] = sprintf(
            __( 'PHP 7.4+ es requerido. Versión actual: %s', 'woocommerce-megasoft-gateway-v2' ),
            PHP_VERSION
        );
    }

    // Verificar extensiones PHP necesarias
    $required_extensions = array( 'curl', 'json', 'openssl', 'xml', 'simplexml' );
    foreach ( $required_extensions as $ext ) {
        if ( ! extension_loaded( $ext ) ) {
            $errors[] = sprintf(
                __( 'Extensión PHP requerida no encontrada: %s', 'woocommerce-megasoft-gateway-v2' ),
                $ext
            );
        }
    }

    // Verificar SSL (CRÍTICO para modalidad NO UNIVERSAL)
    if ( ! is_ssl() && ! defined( 'MEGASOFT_V2_ALLOW_NO_SSL' ) ) {
        $errors[] = __( 'ADVERTENCIA: SSL es OBLIGATORIO para la modalidad NO UNIVERSAL. Activa HTTPS en tu servidor.', 'woocommerce-megasoft-gateway-v2' );
    }

    return $errors;
}

/**
 * Mostrar notices de errores
 */
function megasoft_v2_requirements_notice() {
    $errors = megasoft_v2_check_requirements();

    if ( empty( $errors ) ) {
        return;
    }

    ?>
    <div class="error">
        <p>
            <strong><?php esc_html_e( 'Mega Soft Gateway v2 (NO UNIVERSAL)', 'woocommerce-megasoft-gateway-v2' ); ?></strong>
        </p>
        <ul style="list-style-type: disc; margin-left: 20px;">
            <?php foreach ( $errors as $error ) : ?>
                <li><?php echo esc_html( $error ); ?></li>
            <?php endforeach; ?>
        </ul>
        <?php if ( ! megasoft_v2_check_woocommerce() ) : ?>
            <p>
                <a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Instalar WooCommerce', 'woocommerce-megasoft-gateway-v2' ); ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Cargar archivos del plugin
 */
function megasoft_v2_load_files() {
    // Cargar clases base
    $files = array(
        'includes/class-megasoft-v2-logger.php',
        'includes/class-megasoft-v2-security.php',
        'includes/class-megasoft-v2-api.php',
        'includes/class-megasoft-v2-card-validator.php',
        'includes/class-megasoft-v2-payment-methods.php',
        'includes/class-megasoft-v2-webhook.php',
        'includes/class-megasoft-v2-admin.php',
    );

    foreach ( $files as $file ) {
        $filepath = MEGASOFT_V2_PLUGIN_PATH . $file;
        if ( file_exists( $filepath ) ) {
            require_once $filepath;
        }
    }
}

/**
 * Inicializar el plugin
 */
function megasoft_v2_init() {
    // Verificar requisitos
    $errors = megasoft_v2_check_requirements();
    if ( ! empty( $errors ) ) {
        add_action( 'admin_notices', 'megasoft_v2_requirements_notice' );
        return;
    }

    // Cargar archivos
    megasoft_v2_load_files();

    // Registrar el gateway en WooCommerce
    add_filter( 'woocommerce_payment_gateways', 'megasoft_v2_add_gateway' );

    // Inicializar componentes
    megasoft_v2_init_components();

    // Cargar textdomain
    load_plugin_textdomain(
        'woocommerce-megasoft-gateway-v2',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}

/**
 * Agregar gateway a WooCommerce
 */
function megasoft_v2_add_gateway( $gateways ) {
    $gateways[] = 'WC_Gateway_MegaSoft_V2';
    return $gateways;
}

/**
 * Inicializar componentes
 */
function megasoft_v2_init_components() {
    // Inicializar webhook handler
    if ( class_exists( 'MegaSoft_V2_Webhook' ) ) {
        new MegaSoft_V2_Webhook();
    }

    // Inicializar admin solo en backend
    if ( is_admin() && class_exists( 'MegaSoft_V2_Admin' ) ) {
        new MegaSoft_V2_Admin();
    }
}

/**
 * Activación del plugin
 */
function megasoft_v2_activate() {
    // Verificar requisitos antes de activar
    $errors = megasoft_v2_check_requirements();
    if ( ! empty( $errors ) ) {
        wp_die(
            '<h1>' . __( 'Error de activación', 'woocommerce-megasoft-gateway-v2' ) . '</h1>' .
            '<p>' . __( 'No se puede activar el plugin debido a los siguientes errores:', 'woocommerce-megasoft-gateway-v2' ) . '</p>' .
            '<ul><li>' . implode( '</li><li>', array_map( 'esc_html', $errors ) ) . '</li></ul>',
            __( 'Error de Plugin', 'woocommerce-megasoft-gateway-v2' ),
            array( 'back_link' => true )
        );
    }

    // Crear tablas de base de datos
    megasoft_v2_create_tables();

    // Establecer opciones por defecto
    megasoft_v2_set_default_options();

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Desactivación del plugin
 */
function megasoft_v2_deactivate() {
    // Limpiar cron jobs
    wp_clear_scheduled_hook( 'megasoft_v2_sync_transactions' );
    wp_clear_scheduled_hook( 'megasoft_v2_process_pending_webhooks' );
    wp_clear_scheduled_hook( 'megasoft_v2_cleanup_old_logs' );

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Crear tablas de base de datos
 */
function megasoft_v2_create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla de transacciones
    $table_transactions = $wpdb->prefix . 'megasoft_v2_transactions';
    $sql_transactions = "CREATE TABLE IF NOT EXISTS $table_transactions (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id bigint(20) UNSIGNED NOT NULL,
        control_number varchar(19) NOT NULL,
        payment_method varchar(50) DEFAULT NULL,
        status varchar(20) DEFAULT 'pending',
        amount decimal(10,2) NOT NULL,
        auth_id varchar(50) DEFAULT NULL,
        reference varchar(100) DEFAULT NULL,
        document_type char(1) DEFAULT NULL,
        document_number varchar(20) DEFAULT NULL,
        client_name varchar(100) DEFAULT NULL,
        request_data longtext,
        response_data longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY control_number (control_number),
        KEY order_id (order_id),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Tabla de logs
    $table_logs = $wpdb->prefix . 'megasoft_v2_logs';
    $sql_logs = "CREATE TABLE IF NOT EXISTS $table_logs (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id bigint(20) UNSIGNED DEFAULT NULL,
        control_number varchar(19) DEFAULT NULL,
        level varchar(10) NOT NULL,
        message text NOT NULL,
        context longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY level (level),
        KEY order_id (order_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Tabla de webhooks fallidos
    $table_failed_webhooks = $wpdb->prefix . 'megasoft_v2_failed_webhooks';
    $sql_failed_webhooks = "CREATE TABLE IF NOT EXISTS $table_failed_webhooks (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        raw_data longtext NOT NULL,
        error_message text NOT NULL,
        retry_count int(11) DEFAULT 0,
        next_retry datetime DEFAULT NULL,
        processed tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY next_retry (next_retry),
        KEY processed (processed)
    ) $charset_collate;";

    // Tabla de auditoría de seguridad
    $table_security_log = $wpdb->prefix . 'megasoft_v2_security_log';
    $sql_security_log = "CREATE TABLE IF NOT EXISTS $table_security_log (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_type varchar(50) NOT NULL,
        ip_address varchar(45) DEFAULT NULL,
        user_id bigint(20) UNSIGNED DEFAULT NULL,
        event_data longtext,
        severity varchar(20) DEFAULT 'info',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY event_type (event_type),
        KEY ip_address (ip_address),
        KEY created_at (created_at)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql_transactions );
    dbDelta( $sql_logs );
    dbDelta( $sql_failed_webhooks );
    dbDelta( $sql_security_log );

    // Guardar versión de la base de datos
    update_option( 'megasoft_v2_db_version', MEGASOFT_V2_VERSION );
}

/**
 * Establecer opciones por defecto
 */
function megasoft_v2_set_default_options() {
    $defaults = array(
        'megasoft_v2_first_activation' => current_time( 'mysql' ),
        'megasoft_v2_pci_notice_shown' => 'no',
    );

    foreach ( $defaults as $key => $value ) {
        if ( ! get_option( $key ) ) {
            update_option( $key, $value );
        }
    }
}

/**
 * Mostrar advertencia PCI al activar
 */
function megasoft_v2_pci_compliance_notice() {
    if ( get_option( 'megasoft_v2_pci_notice_shown' ) === 'yes' ) {
        return;
    }

    ?>
    <div class="notice notice-warning is-dismissible megasoft-v2-pci-notice">
        <h2><?php esc_html_e( '⚠️ IMPORTANTE: Certificación PCI DSS Requerida', 'woocommerce-megasoft-gateway-v2' ); ?></h2>
        <p><strong><?php esc_html_e( 'Has activado la Modalidad NO UNIVERSAL de Mega Soft Gateway.', 'woocommerce-megasoft-gateway-v2' ); ?></strong></p>
        <p><?php esc_html_e( 'Esta modalidad captura datos de tarjetas directamente en tu sitio web, lo cual requiere:', 'woocommerce-megasoft-gateway-v2' ); ?></p>
        <ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 10px;">
            <li><?php esc_html_e( '✓ Certificación PCI DSS obligatoria', 'woocommerce-megasoft-gateway-v2' ); ?></li>
            <li><?php esc_html_e( '✓ Certificado SSL válido (NO self-signed)', 'woocommerce-megasoft-gateway-v2' ); ?></li>
            <li><?php esc_html_e( '✓ Certificación previa de Mega Soft Computación C.A.', 'woocommerce-megasoft-gateway-v2' ); ?></li>
            <li><?php esc_html_e( '✓ Aprobación de bancos adquirientes', 'woocommerce-megasoft-gateway-v2' ); ?></li>
        </ul>
        <p>
            <strong style="color: #d63638;"><?php esc_html_e( '⚠️ NO uses en producción sin certificación. Tu cuenta puede ser suspendida.', 'woocommerce-megasoft-gateway-v2' ); ?></strong>
        </p>
        <p>
            <a href="mailto:merchant@megasoft.com.ve" class="button button-primary"><?php esc_html_e( 'Contactar a Mega Soft para Certificación', 'woocommerce-megasoft-gateway-v2' ); ?></a>
            <button type="button" class="button megasoft-v2-dismiss-pci-notice"><?php esc_html_e( 'Entendido', 'woocommerce-megasoft-gateway-v2' ); ?></button>
        </p>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('.megasoft-v2-dismiss-pci-notice').on('click', function() {
            $.post(ajaxurl, {
                action: 'megasoft_v2_dismiss_pci_notice',
                nonce: '<?php echo wp_create_nonce( 'megasoft_v2_dismiss_pci' ); ?>'
            }, function() {
                $('.megasoft-v2-pci-notice').fadeOut();
            });
        });
    });
    </script>
    <?php
}

/**
 * AJAX: Descartar aviso PCI
 */
function megasoft_v2_ajax_dismiss_pci_notice() {
    check_ajax_referer( 'megasoft_v2_dismiss_pci', 'nonce' );
    update_option( 'megasoft_v2_pci_notice_shown', 'yes' );
    wp_send_json_success();
}
add_action( 'wp_ajax_megasoft_v2_dismiss_pci_notice', 'megasoft_v2_ajax_dismiss_pci_notice' );

// Hooks de activación/desactivación
register_activation_hook( __FILE__, 'megasoft_v2_activate' );
register_deactivation_hook( __FILE__, 'megasoft_v2_deactivate' );

// Inicializar plugin
add_action( 'plugins_loaded', 'megasoft_v2_init', 11 );

// Mostrar aviso PCI
add_action( 'admin_notices', 'megasoft_v2_pci_compliance_notice' );

/**
 * Agregar enlaces en la página de plugins
 */
function megasoft_v2_plugin_action_links( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=megasoft_v2' ) . '">' .
        __( 'Configuración', 'woocommerce-megasoft-gateway-v2' ) . '</a>';
    $docs_link = '<a href="' . admin_url( 'admin.php?page=megasoft-v2-dashboard' ) . '">' .
        __( 'Dashboard', 'woocommerce-megasoft-gateway-v2' ) . '</a>';

    array_unshift( $links, $settings_link, $docs_link );

    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'megasoft_v2_plugin_action_links' );
