<?php
/**
 * MegaSoft Diagnostics UI
 * Interfaz de usuario para el sistema de diagnóstico
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MegaSoft_Diagnostics_UI {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_diagnostics_menu' ), 20 );
        add_action( 'wp_ajax_megasoft_run_diagnostics', array( $this, 'ajax_run_diagnostics' ) );
        add_action( 'wp_ajax_megasoft_disable_simulator', array( $this, 'ajax_disable_simulator' ) );
    }

    /**
     * Agregar menú de diagnóstico
     */
    public function add_diagnostics_menu() {
        add_submenu_page(
            'megasoft-dashboard',
            __( 'Diagnóstico', 'woocommerce-megasoft-gateway-universal' ),
            __( '🔍 Diagnóstico', 'woocommerce-megasoft-gateway-universal' ),
            'manage_options',
            'megasoft-diagnostics',
            array( $this, 'render_diagnostics_page' )
        );
    }

    /**
     * Renderizar página de diagnóstico
     */
    public function render_diagnostics_page() {
        ?>
        <div class="wrap megasoft-diagnostics-wrap">
            <h1>🔍 Diagnóstico del Sistema Mega Soft Gateway</h1>

            <div class="megasoft-diagnostics-intro">
                <p>Esta herramienta ejecuta una serie completa de verificaciones para identificar problemas de conexión con la plataforma Mega Soft.</p>
                <p><strong>El diagnóstico verifica:</strong></p>
                <ul style="list-style: disc; margin-left: 30px;">
                    <li>Estado del simulador de PG inactivo</li>
                    <li>Configuración del gateway (modo prueba/producción)</li>
                    <li>Credenciales de API</li>
                    <li>Conectividad con servidores de Mega Soft</li>
                    <li>Certificados SSL</li>
                    <li>Requisitos del sistema (PHP, cURL, WordPress, WooCommerce)</li>
                    <li>Base de datos</li>
                    <li>Prueba real de pre-registro</li>
                    <li>Logs de errores recientes</li>
                </ul>
            </div>

            <div class="megasoft-diagnostics-actions">
                <button type="button" id="megasoft-run-diagnostic" class="button button-primary button-hero">
                    <span class="dashicons dashicons-update-alt"></span>
                    Ejecutar Diagnóstico Completo
                </button>

                <?php if ( get_option( 'megasoft_simulate_pg_inactive' ) ) : ?>
                    <button type="button" id="megasoft-disable-simulator" class="button button-secondary button-hero" style="margin-left: 10px;">
                        <span class="dashicons dashicons-warning"></span>
                        Desactivar Simulador PG Inactivo
                    </button>
                <?php endif; ?>
            </div>

            <div id="megasoft-diagnostics-results" class="megasoft-diagnostics-results" style="display: none;">
                <!-- Los resultados se cargarán aquí -->
            </div>

            <div id="megasoft-diagnostics-loading" class="megasoft-diagnostics-loading" style="display: none;">
                <div class="loading-spinner">
                    <span class="spinner is-active"></span>
                    <p>Ejecutando diagnóstico... Por favor espera.</p>
                </div>
            </div>
        </div>

        <style>
        .megasoft-diagnostics-wrap {
            max-width: 1200px;
        }

        .megasoft-diagnostics-intro {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #2271b1;
        }

        .megasoft-diagnostics-intro p {
            margin: 10px 0;
        }

        .megasoft-diagnostics-actions {
            margin: 30px 0;
        }

        .megasoft-diagnostics-loading {
            text-align: center;
            padding: 50px;
            background: #fff;
            border: 1px solid #ccd0d4;
            margin: 20px 0;
        }

        .megasoft-diagnostics-loading .spinner {
            float: none;
            margin: 0 auto;
        }

        .megasoft-diagnostics-results {
            margin: 20px 0;
        }

        .diagnostic-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin-bottom: 15px;
        }

        .diagnostic-section-header {
            background: #f6f7f7;
            padding: 15px 20px;
            border-bottom: 1px solid #ccd0d4;
            font-size: 16px;
            font-weight: 600;
        }

        .diagnostic-items {
            padding: 0;
        }

        .diagnostic-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f1;
            display: flex;
            align-items: flex-start;
        }

        .diagnostic-item:last-child {
            border-bottom: none;
        }

        .diagnostic-item-icon {
            font-size: 24px;
            margin-right: 15px;
            line-height: 1;
            min-width: 30px;
        }

        .diagnostic-item-content {
            flex: 1;
        }

        .diagnostic-item-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .diagnostic-item-message {
            font-size: 13px;
            color: #50575e;
            margin-bottom: 5px;
        }

        .diagnostic-item-details {
            font-size: 12px;
            color: #787c82;
            background: #f6f7f7;
            padding: 8px 12px;
            border-radius: 4px;
            margin-top: 8px;
            white-space: pre-wrap;
            font-family: monospace;
        }

        /* Estilos por tipo */
        .diagnostic-item.type-success .diagnostic-item-icon {
            color: #00a32a;
        }

        .diagnostic-item.type-error .diagnostic-item-icon {
            color: #d63638;
        }

        .diagnostic-item.type-error {
            background: #fff5f5;
        }

        .diagnostic-item.type-warning .diagnostic-item-icon {
            color: #dba617;
        }

        .diagnostic-item.type-warning {
            background: #fffbf0;
        }

        .diagnostic-item.type-info .diagnostic-item-icon {
            color: #2271b1;
        }

        .diagnostic-item.type-header {
            background: #f6f7f7;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 15px;
            border-bottom: 2px solid #2271b1;
        }

        .diagnostic-summary {
            background: #fff;
            border: 3px solid #2271b1;
            box-shadow: 0 2px 6px rgba(0,0,0,.1);
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }

        .diagnostic-summary.has-errors {
            border-color: #d63638;
        }

        .diagnostic-summary.has-warnings {
            border-color: #dba617;
        }

        .diagnostic-summary h2 {
            margin: 0 0 15px 0;
            font-size: 24px;
        }

        .diagnostic-summary-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
        }

        .diagnostic-stat {
            text-align: center;
        }

        .diagnostic-stat-value {
            font-size: 36px;
            font-weight: bold;
            line-height: 1;
        }

        .diagnostic-stat-label {
            font-size: 13px;
            color: #50575e;
            margin-top: 5px;
        }

        .diagnostic-stat.success .diagnostic-stat-value {
            color: #00a32a;
        }

        .diagnostic-stat.error .diagnostic-stat-value {
            color: #d63638;
        }

        .diagnostic-stat.warning .diagnostic-stat-value {
            color: #dba617;
        }

        .megasoft-action-buttons {
            margin-top: 20px;
        }

        .megasoft-action-buttons .button {
            margin-right: 10px;
        }
        </style>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#megasoft-run-diagnostic').on('click', function() {
                var $button = $(this);
                var $results = $('#megasoft-diagnostics-results');
                var $loading = $('#megasoft-diagnostics-loading');

                $button.prop('disabled', true);
                $results.hide();
                $loading.show();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'megasoft_run_diagnostics',
                        nonce: '<?php echo wp_create_nonce( 'megasoft_diagnostics' ); ?>'
                    },
                    success: function(response) {
                        $loading.hide();
                        $button.prop('disabled', false);

                        if (response.success) {
                            $results.html(response.data.html).show();

                            // Scroll suave al resultado
                            $('html, body').animate({
                                scrollTop: $results.offset().top - 100
                            }, 500);
                        } else {
                            alert('Error: ' + (response.data.message || 'Error desconocido'));
                        }
                    },
                    error: function() {
                        $loading.hide();
                        $button.prop('disabled', false);
                        alert('Error de conexión. Por favor intenta de nuevo.');
                    }
                });
            });

            $('#megasoft-disable-simulator').on('click', function() {
                if (!confirm('¿Desactivar el simulador de PG inactivo?')) {
                    return;
                }

                var $button = $(this);
                $button.prop('disabled', true);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'megasoft_disable_simulator',
                        nonce: '<?php echo wp_create_nonce( 'megasoft_diagnostics' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Simulador desactivado correctamente.');
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data.message || 'Error desconocido'));
                            $button.prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Error de conexión. Por favor intenta de nuevo.');
                        $button.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Ejecutar diagnóstico
     */
    public function ajax_run_diagnostics() {
        check_ajax_referer( 'megasoft_diagnostics', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permisos insuficientes' ) );
        }

        // Cargar clase de diagnóstico
        require_once MEGASOFT_PLUGIN_PATH . 'includes/class-megasoft-diagnostics.php';

        $diagnostics = new MegaSoft_Diagnostics();
        $result = $diagnostics->run_full_diagnostic();

        // Generar HTML
        $html = $this->generate_results_html( $result );

        wp_send_json_success( array( 'html' => $html ) );
    }

    /**
     * AJAX: Desactivar simulador
     */
    public function ajax_disable_simulator() {
        check_ajax_referer( 'megasoft_diagnostics', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permisos insuficientes' ) );
        }

        delete_option( 'megasoft_simulate_pg_inactive' );

        wp_send_json_success( array( 'message' => 'Simulador desactivado' ) );
    }

    /**
     * Generar HTML de resultados
     */
    private function generate_results_html( $result ) {
        ob_start();

        $has_errors = $result['error_count'] > 0;
        $has_warnings = $result['warning_count'] > 0;
        $summary_class = $has_errors ? 'has-errors' : ( $has_warnings ? 'has-warnings' : '' );

        ?>
        <div class="diagnostic-summary <?php echo esc_attr( $summary_class ); ?>">
            <?php if ( $has_errors ) : ?>
                <h2>❌ Se encontraron problemas críticos</h2>
                <p>Debes resolver los errores mostrados abajo para que el gateway funcione correctamente.</p>
            <?php elseif ( $has_warnings ) : ?>
                <h2>⚠️ Se encontraron advertencias</h2>
                <p>El gateway puede funcionar, pero se recomienda revisar las advertencias.</p>
            <?php else : ?>
                <h2>✅ ¡Todo está en orden!</h2>
                <p>No se encontraron problemas. El gateway debería funcionar correctamente.</p>
            <?php endif; ?>

            <div class="diagnostic-summary-stats">
                <div class="diagnostic-stat success">
                    <div class="diagnostic-stat-value"><?php echo esc_html( $result['success_count'] ); ?></div>
                    <div class="diagnostic-stat-label">Exitosas</div>
                </div>
                <div class="diagnostic-stat warning">
                    <div class="diagnostic-stat-value"><?php echo esc_html( $result['warning_count'] ); ?></div>
                    <div class="diagnostic-stat-label">Advertencias</div>
                </div>
                <div class="diagnostic-stat error">
                    <div class="diagnostic-stat-value"><?php echo esc_html( $result['error_count'] ); ?></div>
                    <div class="diagnostic-stat-label">Errores</div>
                </div>
            </div>

            <?php if ( $has_errors || $has_warnings ) : ?>
                <div class="megasoft-action-buttons">
                    <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=megasoft_gateway_universal' ); ?>" class="button button-primary">
                        Ir a Configuración
                    </a>
                    <button type="button" onclick="window.print()" class="button button-secondary">
                        Imprimir Reporte
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div class="diagnostic-section">
            <div class="diagnostic-items">
                <?php foreach ( $result['results'] as $item ) : ?>
                    <?php if ( $item['type'] === 'header' ) : ?>
                        <div class="diagnostic-item type-header">
                            <?php echo esc_html( $item['title'] ); ?>
                        </div>
                    <?php else : ?>
                        <div class="diagnostic-item type-<?php echo esc_attr( $item['type'] ); ?>">
                            <div class="diagnostic-item-icon">
                                <?php
                                switch ( $item['type'] ) {
                                    case 'success':
                                        echo '✅';
                                        break;
                                    case 'error':
                                        echo '❌';
                                        break;
                                    case 'warning':
                                        echo '⚠️';
                                        break;
                                    case 'info':
                                        echo 'ℹ️';
                                        break;
                                }
                                ?>
                            </div>
                            <div class="diagnostic-item-content">
                                <div class="diagnostic-item-title">
                                    <?php echo esc_html( $item['title'] ); ?>
                                </div>
                                <div class="diagnostic-item-message">
                                    <?php echo wp_kses_post( nl2br( $item['message'] ) ); ?>
                                </div>
                                <?php if ( ! empty( $item['details'] ) ) : ?>
                                    <div class="diagnostic-item-details">
                                        <?php echo esc_html( $item['details'] ); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ( $has_errors ) : ?>
            <div class="notice notice-error" style="margin-top: 30px; padding: 20px;">
                <h3>🚨 Pasos siguientes para resolver los errores:</h3>
                <ol style="margin-left: 20px;">
                    <?php foreach ( $result['errors'] as $error ) : ?>
                        <li><strong><?php echo esc_html( $error['title'] ); ?>:</strong> <?php echo esc_html( $error['message'] ); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        <?php endif; ?>
        <?php

        return ob_get_clean();
    }
}

// Inicializar solo si estamos en admin
if ( is_admin() ) {
    new MegaSoft_Diagnostics_UI();
}
