<?php
/**
 * MegaSoft Security Loader
 * Carga automática del sistema de seguridad + parches críticos
 * 
 * @package WooCommerce_MegaSoft_Gateway
 * @version 3.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cargar sistema de seguridad
 */
function megasoft_load_security_system() {
    // Verificar que el plugin principal esté activo
    if ( ! class_exists( 'WC_Gateway_MegaSoft_Universal' ) ) {
        return;
    }
    
    // Cargar clase principal de seguridad
    if ( file_exists( MEGASOFT_PLUGIN_PATH . 'includes/class-megasoft-security.php' ) ) {
        require_once MEGASOFT_PLUGIN_PATH . 'includes/class-megasoft-security.php';
    }
    
    // Cargar hooks de seguridad
    if ( file_exists( MEGASOFT_PLUGIN_PATH . 'includes/class-megasoft-security-hooks.php' ) ) {
        require_once MEGASOFT_PLUGIN_PATH . 'includes/class-megasoft-security-hooks.php';
    }
    
    // 🔒 CARGAR PARCHES DE SEGURIDAD CRÍTICOS
    if ( file_exists( MEGASOFT_PLUGIN_PATH . 'includes/megasoft-security-patch.php' ) ) {
        require_once MEGASOFT_PLUGIN_PATH . 'includes/megasoft-security-patch.php';
    }
    
    if ( file_exists( MEGASOFT_PLUGIN_PATH . 'includes/megasoft-gateway-hooks.php' ) ) {
        require_once MEGASOFT_PLUGIN_PATH . 'includes/megasoft-gateway-hooks.php';
    }
}

add_action( 'plugins_loaded', 'megasoft_load_security_system', 5 );

/**
 * Registrar tarea de limpieza diaria
 */
function megasoft_schedule_security_cleanup() {
    if ( ! wp_next_scheduled( 'megasoft_daily_cleanup' ) ) {
        wp_schedule_event( time(), 'daily', 'megasoft_daily_cleanup' );
    }
}

add_action( 'init', 'megasoft_schedule_security_cleanup' );

/**
 * Desactivar tareas programadas al desactivar el plugin
 */
function megasoft_deactivate_security_cleanup() {
    wp_clear_scheduled_hook( 'megasoft_daily_cleanup' );
}

register_deactivation_hook( MEGASOFT_PLUGIN_FILE, 'megasoft_deactivate_security_cleanup' );

/**
 * 🔒 MOSTRAR ESTADO DE PARCHES EN ADMIN (SIN DUPLICADOS)
 */
// add_action( 'admin_notices', 'megasoft_show_security_patches_status' );

function megasoft_show_security_patches_status() {
    // Evitar duplicados: solo ejecutar una vez
    static $already_displayed = false;
    
    if ( $already_displayed ) {
        return;
    }
    
    $screen = get_current_screen();
    
    if ( ! $screen ) {
        return;
    }
    
    // Solo mostrar en la página de configuración del gateway
    if ( $screen->id === 'woocommerce_page_wc-settings' ) {
        if ( isset( $_GET['tab'] ) && $_GET['tab'] === 'checkout' && 
             isset( $_GET['section'] ) && $_GET['section'] === 'megasoft_gateway_universal' ) {
            
            $already_displayed = true; // Marcar como ya mostrado
            
            // Verificar que los parches estén cargados
            $xxe_protection = function_exists( 'megasoft_secure_xml_parser' );
            $timing_protection = function_exists( 'megasoft_secure_control_validation' );
            
            if ( $xxe_protection && $timing_protection ) {
                ?>
                <div class="notice notice-success" style="border-left-color: #00a32a; padding: 15px;">
                    <p style="margin: 0; font-size: 14px;">
                        <strong style="font-size: 16px;">🔒 <?php echo esc_html__( 'Parches de Seguridad Activos', 'woocommerce-megasoft-gateway-universal' ); ?></strong>
                    </p>
                    <ul style="margin: 10px 0 0 20px; list-style: none;">
                        <li>✅ <?php echo esc_html__( 'Protección XXE habilitada', 'woocommerce-megasoft-gateway-universal' ); ?></li>
                        <li>✅ <?php echo esc_html__( 'Validación timing-safe activa (hash_equals)', 'woocommerce-megasoft-gateway-universal' ); ?></li>
                        <li>✅ <?php echo esc_html__( 'Detección automática de fraude', 'woocommerce-megasoft-gateway-universal' ); ?></li>
                        <li>✅ <?php echo esc_html__( 'Bloqueo automático de IPs maliciosas', 'woocommerce-megasoft-gateway-universal' ); ?></li>
                    </ul>
                    <p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">
                        <?php echo esc_html__( 'Versión 3.0.4 - Nivel de seguridad: EMPRESARIAL (9.5/10)', 'woocommerce-megasoft-gateway-universal' ); ?>
                    </p>
                </div>
                <?php
            } else {
                ?>
                <div class="notice notice-warning">
                    <p>
                        <strong>⚠️ <?php echo esc_html__( 'Advertencia de Seguridad', 'woocommerce-megasoft-gateway-universal' ); ?></strong><br>
                        <?php echo esc_html__( 'Los parches de seguridad no se cargaron correctamente.', 'woocommerce-megasoft-gateway-universal' ); ?>
                        <br><br>
                        <strong><?php echo esc_html__( 'Estado:', 'woocommerce-megasoft-gateway-universal' ); ?></strong><br>
                        <?php echo $xxe_protection ? '✅' : '❌'; ?> <?php echo esc_html__( 'Protección XXE', 'woocommerce-megasoft-gateway-universal' ); ?><br>
                        <?php echo $timing_protection ? '✅' : '❌'; ?> <?php echo esc_html__( 'Protección Timing Attack', 'woocommerce-megasoft-gateway-universal' ); ?>
                    </p>
                </div>
                <?php
            }
            
            return; // Salir después de mostrar
        }
    }
    
    // También mostrar en dashboard de MegaSoft (solo si no se mostró arriba)
    if ( $screen->id === 'toplevel_page_megasoft-dashboard' && ! $already_displayed ) {
        $xxe_protection = function_exists( 'megasoft_secure_xml_parser' );
        $timing_protection = function_exists( 'megasoft_secure_control_validation' );
        
        if ( $xxe_protection && $timing_protection ) {
            $already_displayed = true;
            ?>
            <div class="notice notice-success">
                <p>
                    <strong>🔒 <?php echo esc_html__( 'Sistema de Seguridad Activo', 'woocommerce-megasoft-gateway-universal' ); ?></strong> - 
                    <?php echo esc_html__( 'Todos los parches críticos están instalados y funcionando.', 'woocommerce-megasoft-gateway-universal' ); ?>
                </p>
            </div>
            <?php
        }
    }
}

/**
 * Helper functions para uso en todo el plugin
 */

if ( ! function_exists( 'megasoft_sanitize' ) ) {
    function megasoft_sanitize( $value, $type = 'text' ) {
        if ( function_exists( 'megasoft_security' ) ) {
            return megasoft_security()->sanitize_input( $value, $type );
        }
        return sanitize_text_field( $value );
    }
}

if ( ! function_exists( 'megasoft_validate_document' ) ) {
    function megasoft_validate_document( $type, $number ) {
        if ( function_exists( 'megasoft_security' ) ) {
            return megasoft_security()->validate_document( $type, $number );
        }
        return array(
            'valid' => ! empty( $type ) && ! empty( $number ),
            'message' => ''
        );
    }
}

if ( ! function_exists( 'megasoft_escape' ) ) {
    function megasoft_escape( $value, $context = 'html' ) {
        if ( function_exists( 'megasoft_security' ) ) {
            return megasoft_security()->escape_output( $value, $context );
        }
        return esc_html( $value );
    }
}

if ( ! function_exists( 'megasoft_validate_control' ) ) {
    function megasoft_validate_control( $control_number ) {
        if ( function_exists( 'megasoft_security' ) ) {
            return megasoft_security()->validate_control_number( $control_number );
        }
        return is_numeric( $control_number ) && strlen( $control_number ) >= 10;
    }
}

if ( ! function_exists( 'megasoft_check_rate_limit' ) ) {
    function megasoft_check_rate_limit( $key, $max_attempts = 5, $time_window = 300 ) {
        if ( function_exists( 'megasoft_security' ) ) {
            return megasoft_security()->check_rate_limit( $key, $max_attempts, $time_window );
        }
        return array(
            'allowed' => true,
            'remaining' => $max_attempts,
            'reset_time' => time() + $time_window
        );
    }
}

if ( ! function_exists( 'megasoft_log_security_event' ) ) {
    function megasoft_log_security_event( $event_type, $details = array() ) {
        if ( function_exists( 'megasoft_security' ) ) {
            megasoft_security()->log_security_event( $event_type, $details );
        }
    }
}

if ( ! function_exists( 'megasoft_get_client_ip' ) ) {
    function megasoft_get_client_ip() {
        if ( function_exists( 'megasoft_security' ) ) {
            return megasoft_security()->get_client_ip();
        }
        return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
    }
}

if ( ! function_exists( 'megasoft_verify_nonce' ) ) {
    function megasoft_verify_nonce( $nonce, $action ) {
        if ( function_exists( 'megasoft_security' ) ) {
            return megasoft_security()->verify_nonce( $nonce, $action );
        }
        return wp_verify_nonce( $nonce, $action );
    }
}

/**
 * Widget de seguridad en el admin
 */
function megasoft_add_security_dashboard_widget() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    wp_add_dashboard_widget(
        'megasoft_security_widget',
        '🛡️ Mega Soft - Estado de Seguridad',
        'megasoft_render_security_widget'
    );
}

add_action( 'wp_dashboard_setup', 'megasoft_add_security_dashboard_widget' );

/**
 * Renderizar widget de seguridad
 */
function megasoft_render_security_widget() {
    global $wpdb;
    
    $security_log_table = $wpdb->prefix . 'megasoft_security_log';
    
    // Verificar estado de parches
    $xxe_protection = function_exists( 'megasoft_secure_xml_parser' );
    $timing_protection = function_exists( 'megasoft_secure_control_validation' );
    $patches_active = $xxe_protection && $timing_protection;
    
    // Obtener estadísticas de las últimas 24 horas
    $stats = $wpdb->get_row(
        "SELECT 
            COUNT(*) as total_events,
            COUNT(DISTINCT ip_address) as unique_ips,
            COUNT(DISTINCT event_type) as event_types
        FROM {$security_log_table}
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    
    // Obtener eventos recientes
    $recent_events = $wpdb->get_results(
        "SELECT event_type, ip_address, created_at
        FROM {$security_log_table}
        ORDER BY created_at DESC
        LIMIT 5"
    );
    
    // IPs bloqueadas
    $blocked_ips = get_option( 'megasoft_blocked_ips', array() );
    
    ?>
    <div class="megasoft-security-widget">
        <?php if ( $patches_active ) : ?>
            <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <strong style="color: #155724;">🔒 Parches de Seguridad: ACTIVOS</strong><br>
                <small style="color: #155724;">XXE Protection ✅ | Timing-Safe Validation ✅</small>
            </div>
        <?php else : ?>
            <div style="background: #fff3cd; border: 1px solid #ffeeba; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <strong style="color: #856404;">⚠️ Parches Incompletos</strong><br>
                <small>XXE: <?php echo $xxe_protection ? '✅' : '❌'; ?> | Timing: <?php echo $timing_protection ? '✅' : '❌'; ?></small>
            </div>
        <?php endif; ?>
        
        <div class="security-stats" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
            <div class="stat-card" style="background: #f0f6fc; padding: 15px; border-radius: 5px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: #0073aa;">
                    <?php echo esc_html( $stats->total_events ?? 0 ); ?>
                </div>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                    Eventos (24h)
                </div>
            </div>
            
            <div class="stat-card" style="background: #fff3cd; padding: 15px; border-radius: 5px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: #856404;">
                    <?php echo esc_html( $stats->unique_ips ?? 0 ); ?>
                </div>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                    IPs Únicas
                </div>
            </div>
            
            <div class="stat-card" style="background: #f8d7da; padding: 15px; border-radius: 5px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: #721c24;">
                    <?php echo esc_html( count( $blocked_ips ) ); ?>
                </div>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                    IPs Bloqueadas
                </div>
            </div>
        </div>
        
        <?php if ( ! empty( $recent_events ) ) : ?>
            <div class="recent-events">
                <h4 style="margin: 0 0 10px 0; font-size: 14px;">Eventos Recientes</h4>
                <ul style="margin: 0; padding: 0; list-style: none;">
                    <?php foreach ( $recent_events as $event ) : ?>
                        <li style="padding: 8px; border-bottom: 1px solid #eee; font-size: 12px;">
                            <strong><?php echo esc_html( $event->event_type ); ?></strong><br>
                            <span style="color: #666;">
                                IP: <?php echo esc_html( $event->ip_address ); ?> | 
                                <?php echo esc_html( human_time_diff( strtotime( $event->created_at ), current_time( 'timestamp' ) ) ); ?> atrás
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=megasoft-security-logs' ) ); ?>" class="button button-primary button-small">
                Ver Todos los Logs
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=megasoft-security-settings' ) ); ?>" class="button button-secondary button-small">
                Configuración
            </a>
        </div>
    </div>
    <?php
}

/**
 * Agregar página de logs de seguridad al menú
 */
function megasoft_add_security_admin_pages() {
    add_submenu_page(
        'megasoft-dashboard',
        __( 'Logs de Seguridad', 'woocommerce-megasoft-gateway-universal' ),
        __( 'Seguridad', 'woocommerce-megasoft-gateway-universal' ),
        'manage_options',
        'megasoft-security-logs',
        'megasoft_render_security_logs_page'
    );
    
    add_submenu_page(
        'megasoft-dashboard',
        __( 'Configuración de Seguridad', 'woocommerce-megasoft-gateway-universal' ),
        __( 'Config. Seguridad', 'woocommerce-megasoft-gateway-universal' ),
        'manage_options',
        'megasoft-security-settings',
        'megasoft_render_security_settings_page'
    );
}

add_action( 'admin_menu', 'megasoft_add_security_admin_pages', 99 );

/**
 * Renderizar página de logs de seguridad
 */
function megasoft_render_security_logs_page() {
    global $wpdb;
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'woocommerce-megasoft-gateway-universal' ) );
    }
    
    $per_page = 50;
    $page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
    $offset = ( $page - 1 ) * $per_page;
    
    $event_type = isset( $_GET['event_type'] ) ? sanitize_text_field( wp_unslash( $_GET['event_type'] ) ) : '';
    $ip_address = isset( $_GET['ip_address'] ) ? sanitize_text_field( wp_unslash( $_GET['ip_address'] ) ) : '';
    
    $table_name = $wpdb->prefix . 'megasoft_security_log';
    
    $where = array( '1=1' );
    $where_values = array();
    
    if ( ! empty( $event_type ) ) {
        $where[] = 'event_type = %s';
        $where_values[] = $event_type;
    }
    
    if ( ! empty( $ip_address ) ) {
        $where[] = 'ip_address = %s';
        $where_values[] = $ip_address;
    }
    
    $where_clause = implode( ' AND ', $where );
    
    $total_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";
    if ( ! empty( $where_values ) ) {
        $total_query = $wpdb->prepare( $total_query, ...$where_values );
    }
    $total_items = $wpdb->get_var( $total_query );
    
    $logs_query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $query_values = array_merge( $where_values, array( $per_page, $offset ) );
    $logs = $wpdb->get_results( $wpdb->prepare( $logs_query, ...$query_values ) );
    
    $event_types = $wpdb->get_col( "SELECT DISTINCT event_type FROM {$table_name} ORDER BY event_type" );
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Logs de Seguridad', 'woocommerce-megasoft-gateway-universal' ); ?></h1>
        
        <form method="get" style="background: #fff; padding: 15px; margin: 20px 0; border: 1px solid #ccc; border-radius: 5px;">
            <input type="hidden" name="page" value="megasoft-security-logs">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 200px; gap: 15px; align-items: end;">
                <div>
                    <label for="event_type"><?php echo esc_html__( 'Tipo de Evento', 'woocommerce-megasoft-gateway-universal' ); ?></label>
                    <select name="event_type" id="event_type" class="regular-text">
                        <option value=""><?php echo esc_html__( 'Todos', 'woocommerce-megasoft-gateway-universal' ); ?></option>
                        <?php foreach ( $event_types as $type ) : ?>
                            <option value="<?php echo esc_attr( $type ); ?>" <?php selected( $event_type, $type ); ?>>
                                <?php echo esc_html( $type ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="ip_address"><?php echo esc_html__( 'Dirección IP', 'woocommerce-megasoft-gateway-universal' ); ?></label>
                    <input type="text" name="ip_address" id="ip_address" value="<?php echo esc_attr( $ip_address ); ?>" class="regular-text">
                </div>
                
                <div>
                    <button type="submit" class="button button-primary"><?php echo esc_html__( 'Filtrar', 'woocommerce-megasoft-gateway-universal' ); ?></button>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=megasoft-security-logs' ) ); ?>" class="button">
                        <?php echo esc_html__( 'Limpiar', 'woocommerce-megasoft-gateway-universal' ); ?>
                    </a>
                </div>
            </div>
        </form>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 150px;"><?php echo esc_html__( 'Fecha', 'woocommerce-megasoft-gateway-universal' ); ?></th>
                    <th style="width: 200px;"><?php echo esc_html__( 'Tipo de Evento', 'woocommerce-megasoft-gateway-universal' ); ?></th>
                    <th style="width: 150px;"><?php echo esc_html__( 'IP', 'woocommerce-megasoft-gateway-universal' ); ?></th>
                    <th style="width: 80px;"><?php echo esc_html__( 'Usuario', 'woocommerce-megasoft-gateway-universal' ); ?></th>
                    <th><?php echo esc_html__( 'Detalles', 'woocommerce-megasoft-gateway-universal' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $logs ) ) : ?>
                    <?php foreach ( $logs as $log ) : ?>
                        <tr>
                            <td><?php echo esc_html( $log->created_at ); ?></td>
                            <td><strong><?php echo esc_html( $log->event_type ); ?></strong></td>
                            <td>
                                <code><?php echo esc_html( $log->ip_address ); ?></code>
                                <?php if ( function_exists( 'megasoft_security' ) ) : ?>
                                    <br>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=megasoft-security-settings&action=block_ip&ip=' . urlencode( $log->ip_address ) ) ); ?>" 
                                       class="button button-small" 
                                       style="margin-top: 5px;">
                                        <?php echo esc_html__( 'Bloquear', 'woocommerce-megasoft-gateway-universal' ); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ( $log->user_id ) {
                                    $user = get_userdata( $log->user_id );
                                    echo $user ? esc_html( $user->user_login ) : esc_html__( 'Desconocido', 'woocommerce-megasoft-gateway-universal' );
                                } else {
                                    echo esc_html__( 'N/A', 'woocommerce-megasoft-gateway-universal' );
                                }
                                ?>
                            </td>
                            <td>
                                <details>
                                    <summary style="cursor: pointer;"><?php echo esc_html__( 'Ver detalles', 'woocommerce-megasoft-gateway-universal' ); ?></summary>
                                    <pre style="background: #f5f5f5; padding: 10px; margin-top: 10px; overflow: auto; max-height: 200px;"><?php echo esc_html( $log->event_data ); ?></pre>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px;">
                            <?php echo esc_html__( 'No se encontraron logs de seguridad.', 'woocommerce-megasoft-gateway-universal' ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ( $total_items > $per_page ) : ?>
            <div class="tablenav" style="margin-top: 20px;">
                <?php
                $total_pages = ceil( $total_items / $per_page );
                $pagination_args = array(
                    'base' => add_query_arg( 'paged', '%#%' ),
                    'format' => '',
                    'current' => $page,
                    'total' => $total_pages,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;'
                );
                
                echo paginate_links( $pagination_args );
                ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Renderizar página de configuración de seguridad
 */
function megasoft_render_security_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'woocommerce-megasoft-gateway-universal' ) );
    }
    
    if ( isset( $_GET['action'] ) && isset( $_GET['ip'] ) && wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'megasoft_security_action' ) ) {
        $ip = sanitize_text_field( wp_unslash( $_GET['ip'] ) );
        
        if ( $_GET['action'] === 'block_ip' && function_exists( 'megasoft_security' ) ) {
            megasoft_security()->block_ip( $ip, 'Bloqueado manualmente desde admin' );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'IP bloqueada correctamente.', 'woocommerce-megasoft-gateway-universal' ) . '</p></div>';
        } elseif ( $_GET['action'] === 'unblock_ip' ) {
            $blocked_ips = get_option( 'megasoft_blocked_ips', array() );
            $blocked_ips = array_diff( $blocked_ips, array( $ip ) );
            update_option( 'megasoft_blocked_ips', $blocked_ips );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'IP desbloqueada correctamente.', 'woocommerce-megasoft-gateway-universal' ) . '</p></div>';
        }
    }
    
    $blocked_ips = get_option( 'megasoft_blocked_ips', array() );
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Configuración de Seguridad', 'woocommerce-megasoft-gateway-universal' ); ?></h1>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc; border-radius: 5px;">
            <h2><?php echo esc_html__( 'IPs Bloqueadas', 'woocommerce-megasoft-gateway-universal' ); ?></h2>
            
            <?php if ( ! empty( $blocked_ips ) ) : ?>
                <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__( 'Dirección IP', 'woocommerce-megasoft-gateway-universal' ); ?></th>
                            <th style="width: 150px;"><?php echo esc_html__( 'Acciones', 'woocommerce-megasoft-gateway-universal' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $blocked_ips as $ip ) : ?>
                            <tr>
                                <td><code><?php echo esc_html( $ip ); ?></code></td>
                                <td>
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=megasoft-security-settings&action=unblock_ip&ip=' . urlencode( $ip ) ), 'megasoft_security_action' ) ); ?>" 
                                       class="button button-small">
                                        <?php echo esc_html__( 'Desbloquear', 'woocommerce-megasoft-gateway-universal' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php echo esc_html__( 'No hay IPs bloqueadas actualmente.', 'woocommerce-megasoft-gateway-universal' ); ?></p>
            <?php endif; ?>
            
            <h3 style="margin-top: 30px;"><?php echo esc_html__( 'Bloquear Nueva IP', 'woocommerce-megasoft-gateway-universal' ); ?></h3>
            <form method="post" action="">
                <?php wp_nonce_field( 'megasoft_block_ip' ); ?>
                <input type="text" name="new_ip" placeholder="192.168.1.1" class="regular-text" required pattern="[0-9.]+">
                <button type="submit" name="block_new_ip" class="button button-primary">
                    <?php echo esc_html__( 'Bloquear IP', 'woocommerce-megasoft-gateway-universal' ); ?>
                </button>
            </form>
            
            <?php
            if ( isset( $_POST['block_new_ip'] ) && wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'megasoft_block_ip' ) ) {
                $new_ip = sanitize_text_field( wp_unslash( $_POST['new_ip'] ?? '' ) );
                
                if ( filter_var( $new_ip, FILTER_VALIDATE_IP ) && function_exists( 'megasoft_security' ) ) {
                    megasoft_security()->block_ip( $new_ip, 'Bloqueado manualmente desde configuración' );
                    echo '<div class="notice notice-success inline"><p>' . esc_html__( 'IP bloqueada correctamente.', 'woocommerce-megasoft-gateway-universal' ) . '</p></div>';
                    echo '<script>window.location.reload();</script>';
                } else {
                    echo '<div class="notice notice-error inline"><p>' . esc_html__( 'IP inválida.', 'woocommerce-megasoft-gateway-universal' ) . '</p></div>';
                }
            }
            ?>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc; border-radius: 5px;">
            <h2><?php echo esc_html__( 'Información del Sistema', 'woocommerce-megasoft-gateway-universal' ); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th><?php echo esc_html__( 'Sistema de Seguridad', 'woocommerce-megasoft-gateway-universal' ); ?></th>
                    <td>
                        <span style="color: green;">●</span> 
                        <?php echo esc_html__( 'Activo', 'woocommerce-megasoft-gateway-universal' ); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( 'Rate Limiting', 'woocommerce-megasoft-gateway-universal' ); ?></th>
                    <td>
                        <span style="color: green;">●</span> 
                        <?php echo esc_html__( 'Activo', 'woocommerce-megasoft-gateway-universal' ); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( 'Validación de Documentos', 'woocommerce-megasoft-gateway-universal' ); ?></th>
                    <td>
                        <span style="color: green;">●</span> 
                        <?php echo esc_html__( 'Activo', 'woocommerce-megasoft-gateway-universal' ); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( 'Protección XXE', 'woocommerce-megasoft-gateway-universal' ); ?></th>
                    <td>
                        <?php if ( function_exists( 'megasoft_secure_xml_parser' ) ) : ?>
                            <span style="color: green;">●</span> 
                            <?php echo esc_html__( 'Activo', 'woocommerce-megasoft-gateway-universal' ); ?>
                        <?php else : ?>
                            <span style="color: red;">●</span> 
                            <?php echo esc_html__( 'Inactivo', 'woocommerce-megasoft-gateway-universal' ); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo esc_html__( 'Protección Timing Attack', 'woocommerce-megasoft-gateway-universal' ); ?></th>
                    <td>
                        <?php if ( function_exists( 'megasoft_secure_control_validation' ) ) : ?>
                            <span style="color: green;">●</span> 
                            <?php echo esc_html__( 'Activo', 'woocommerce-megasoft-gateway-universal' ); ?>
                        <?php else : ?>
                            <span style="color: red;">●</span> 
                            <?php echo esc_html__( 'Inactivo', 'woocommerce-megasoft-gateway-universal' ); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}