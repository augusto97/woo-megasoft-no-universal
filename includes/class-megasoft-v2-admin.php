<?php
/**
 * MegaSoft Gateway v2 - Admin Dashboard
 *
 * Admin interface for managing transactions, viewing reports,
 * and configuring the payment gateway
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MegaSoft_V2_Admin {

    /**
     * @var MegaSoft_V2_Logger
     */
    private $logger;

    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new MegaSoft_V2_Logger( true, 'info' );

        // Add admin menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

        // Enqueue admin scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // AJAX handlers
        add_action( 'wp_ajax_megasoft_v2_export_transactions', array( $this, 'ajax_export_transactions' ) );
        add_action( 'wp_ajax_megasoft_v2_get_stats', array( $this, 'ajax_get_stats' ) );
        add_action( 'wp_ajax_megasoft_v2_process_refund', array( $this, 'ajax_process_refund' ) );
        add_action( 'wp_ajax_megasoft_v2_clear_logs', array( $this, 'ajax_clear_logs' ) );

        // Add meta boxes to order page
        add_action( 'add_meta_boxes', array( $this, 'add_order_meta_boxes' ) );

        // Add columns to orders list
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_columns' ) );
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_column' ), 10, 2 );

        // Add settings link to plugins page
        add_filter( 'plugin_action_links_' . plugin_basename( MEGASOFT_V2_PLUGIN_FILE ), array( $this, 'add_plugin_action_links' ) );
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu (WordPress automáticamente crea el primer submenu)
        add_menu_page(
            __( 'Mega Soft Gateway', 'woocommerce-megasoft-gateway-v2' ),
            __( 'Mega Soft', 'woocommerce-megasoft-gateway-v2' ),
            'manage_woocommerce',
            'megasoft-v2-dashboard',
            array( $this, 'render_dashboard_page' ),
            'dashicons-money-alt',
            56
        );

        // Transactions submenu
        add_submenu_page(
            'megasoft-v2-dashboard',
            __( 'Transacciones', 'woocommerce-megasoft-gateway-v2' ),
            __( 'Transacciones', 'woocommerce-megasoft-gateway-v2' ),
            'manage_woocommerce',
            'megasoft-v2-transactions',
            array( $this, 'render_transactions_page' )
        );

        // Logs submenu (base de datos)
        add_submenu_page(
            'megasoft-v2-dashboard',
            __( 'Logs', 'woocommerce-megasoft-gateway-v2' ),
            __( 'Logs', 'woocommerce-megasoft-gateway-v2' ),
            'manage_woocommerce',
            'megasoft-v2-logs',
            array( $this, 'render_logs_page' )
        );

        // Diagnostics submenu
        add_submenu_page(
            'megasoft-v2-dashboard',
            __( 'Diagnóstico', 'woocommerce-megasoft-gateway-v2' ),
            __( 'Diagnóstico', 'woocommerce-megasoft-gateway-v2' ),
            'manage_woocommerce',
            'megasoft-v2-diagnostics',
            array( $this, 'render_diagnostics_page' )
        );

        // Settings redirect to WooCommerce settings
        add_submenu_page(
            'megasoft-v2-dashboard',
            __( 'Configuración', 'woocommerce-megasoft-gateway-v2' ),
            __( 'Configuración', 'woocommerce-megasoft-gateway-v2' ),
            'manage_woocommerce',
            'admin.php?page=wc-settings&tab=checkout&section=megasoft_v2'
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on our admin pages
        if ( strpos( $hook, 'megasoft-v2' ) === false && $hook !== 'post.php' && $hook !== 'shop_order' ) {
            return;
        }

        // Admin CSS
        wp_enqueue_style(
            'megasoft-v2-admin',
            plugins_url( 'assets/css/admin.css', MEGASOFT_V2_PLUGIN_FILE ),
            array(),
            MEGASOFT_V2_VERSION
        );

        // Chart.js for statistics
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            array(),
            '3.9.1',
            true
        );

        // Admin JS
        wp_enqueue_script(
            'megasoft-v2-admin',
            plugins_url( 'assets/js/admin.js', MEGASOFT_V2_PLUGIN_FILE ),
            array( 'jquery', 'chartjs' ),
            MEGASOFT_V2_VERSION,
            true
        );

        wp_localize_script(
            'megasoft-v2-admin',
            'megasoftV2Admin',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'megasoft_v2_admin' ),
                'i18n'     => array(
                    'confirm_refund' => __( '¿Estás seguro de procesar este reembolso?', 'woocommerce-megasoft-gateway-v2' ),
                    'confirm_clear'  => __( '¿Estás seguro de limpiar todos los logs?', 'woocommerce-megasoft-gateway-v2' ),
                    'processing'     => __( 'Procesando...', 'woocommerce-megasoft-gateway-v2' ),
                    'success'        => __( 'Operación exitosa', 'woocommerce-megasoft-gateway-v2' ),
                    'error'          => __( 'Error', 'woocommerce-megasoft-gateway-v2' ),
                ),
            )
        );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        $stats = $this->get_dashboard_stats();
        $recent_transactions = $this->get_recent_transactions( 10 );

        ?>
        <div class="wrap megasoft-v2-admin">
            <h1><?php esc_html_e( 'Mega Soft Gateway - Dashboard', 'woocommerce-megasoft-gateway-v2' ); ?></h1>

            <!-- Stats Cards -->
            <div class="megasoft-stats-grid">
                <div class="megasoft-stat-card">
                    <div class="stat-icon dashicons dashicons-cart"></div>
                    <div class="stat-content">
                        <h3><?php echo esc_html( $stats['total_transactions'] ); ?></h3>
                        <p><?php esc_html_e( 'Total Transacciones', 'woocommerce-megasoft-gateway-v2' ); ?></p>
                    </div>
                </div>

                <div class="megasoft-stat-card success">
                    <div class="stat-icon dashicons dashicons-yes-alt"></div>
                    <div class="stat-content">
                        <h3><?php echo esc_html( $stats['approved_transactions'] ); ?></h3>
                        <p><?php esc_html_e( 'Aprobadas', 'woocommerce-megasoft-gateway-v2' ); ?></p>
                    </div>
                </div>

                <div class="megasoft-stat-card error">
                    <div class="stat-icon dashicons dashicons-dismiss"></div>
                    <div class="stat-content">
                        <h3><?php echo esc_html( $stats['failed_transactions'] ); ?></h3>
                        <p><?php esc_html_e( 'Rechazadas', 'woocommerce-megasoft-gateway-v2' ); ?></p>
                    </div>
                </div>

                <div class="megasoft-stat-card warning">
                    <div class="stat-icon dashicons dashicons-money-alt"></div>
                    <div class="stat-content">
                        <h3><?php echo wc_price( $stats['total_amount'] ); ?></h3>
                        <p><?php esc_html_e( 'Total Procesado', 'woocommerce-megasoft-gateway-v2' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="megasoft-charts-grid">
                <div class="megasoft-chart-card">
                    <h2><?php esc_html_e( 'Transacciones (Últimos 7 días)', 'woocommerce-megasoft-gateway-v2' ); ?></h2>
                    <canvas id="megasoft-transactions-chart"></canvas>
                </div>

                <div class="megasoft-chart-card">
                    <h2><?php esc_html_e( 'Tasa de Aprobación', 'woocommerce-megasoft-gateway-v2' ); ?></h2>
                    <canvas id="megasoft-approval-chart"></canvas>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="megasoft-recent-transactions">
                <h2><?php esc_html_e( 'Transacciones Recientes', 'woocommerce-megasoft-gateway-v2' ); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'ID', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                            <th><?php esc_html_e( 'Orden', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                            <th><?php esc_html_e( 'Control', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                            <th><?php esc_html_e( 'Monto', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                            <th><?php esc_html_e( 'Estado', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                            <th><?php esc_html_e( 'Fecha', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $recent_transactions ) ) : ?>
                            <?php foreach ( $recent_transactions as $transaction ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $transaction->id ); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $transaction->order_id . '&action=edit' ) ); ?>">
                                            #<?php echo esc_html( $transaction->order_id ); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html( $transaction->control_number ); ?></td>
                                    <td><?php echo wc_price( $transaction->amount ); ?></td>
                                    <td><?php echo $this->get_status_badge( $transaction->status ); ?></td>
                                    <td><?php echo esc_html( mysql2date( 'd/m/Y H:i', $transaction->created_at ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6"><?php esc_html_e( 'No hay transacciones recientes', 'woocommerce-megasoft-gateway-v2' ); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- System Info -->
            <div class="megasoft-system-info">
                <h2><?php esc_html_e( 'Información del Sistema', 'woocommerce-megasoft-gateway-v2' ); ?></h2>
                <table class="widefat">
                    <tbody>
                        <tr>
                            <th><?php esc_html_e( 'Versión del Plugin', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                            <td><?php echo esc_html( MEGASOFT_V2_VERSION ); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Versión API', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                            <td><?php echo esc_html( MEGASOFT_V2_API_VERSION ); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'SSL Activo', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                            <td><?php echo is_ssl() ? '<span class="megasoft-badge success">Sí</span>' : '<span class="megasoft-badge error">No</span>'; ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'URL Webhook', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                            <td>
                                <code><?php echo esc_url( MegaSoft_V2_Webhook::get_webhook_url() ); ?></code>
                                <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_js( MegaSoft_V2_Webhook::get_webhook_url() ); ?>')">
                                    <?php esc_html_e( 'Copiar', 'woocommerce-megasoft-gateway-v2' ); ?>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <script>
                // Transactions chart data
                const transactionsData = <?php echo json_encode( $this->get_transactions_chart_data() ); ?>;

                // Approval rate data
                const approvalData = {
                    approved: <?php echo intval( $stats['approved_transactions'] ); ?>,
                    failed: <?php echo intval( $stats['failed_transactions'] ); ?>
                };
            </script>
        </div>
        <?php
    }

    /**
     * Render transactions page
     */
    public function render_transactions_page() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_transactions';

        // Pagination
        $per_page = 50;
        $page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $offset = ( $page - 1 ) * $per_page;

        // Filters
        $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

        // Build query
        $where = array();
        if ( $status_filter ) {
            $where[] = $wpdb->prepare( 'status = %s', $status_filter );
        }
        if ( $search ) {
            $where[] = $wpdb->prepare(
                '(control_number LIKE %s OR order_id LIKE %s OR authorization_code LIKE %s)',
                '%' . $wpdb->esc_like( $search ) . '%',
                '%' . $wpdb->esc_like( $search ) . '%',
                '%' . $wpdb->esc_like( $search ) . '%'
            );
        }

        $where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

        // Get total count
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name $where_sql" );
        $total_pages = ceil( $total / $per_page );

        // Get transactions
        $transactions = $wpdb->get_results(
            "SELECT * FROM $table_name $where_sql ORDER BY created_at DESC LIMIT $per_page OFFSET $offset"
        );

        ?>
        <div class="wrap megasoft-v2-admin">
            <h1>
                <?php esc_html_e( 'Transacciones', 'woocommerce-megasoft-gateway-v2' ); ?>
                <button type="button" class="page-title-action" id="megasoft-export-transactions">
                    <?php esc_html_e( 'Exportar CSV', 'woocommerce-megasoft-gateway-v2' ); ?>
                </button>
            </h1>

            <!-- Filters -->
            <div class="megasoft-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="megasoft-v2-transactions" />

                    <select name="status">
                        <option value=""><?php esc_html_e( 'Todos los estados', 'woocommerce-megasoft-gateway-v2' ); ?></option>
                        <option value="approved" <?php selected( $status_filter, 'approved' ); ?>><?php esc_html_e( 'Aprobadas', 'woocommerce-megasoft-gateway-v2' ); ?></option>
                        <option value="failed" <?php selected( $status_filter, 'failed' ); ?>><?php esc_html_e( 'Rechazadas', 'woocommerce-megasoft-gateway-v2' ); ?></option>
                        <option value="pending" <?php selected( $status_filter, 'pending' ); ?>><?php esc_html_e( 'Pendientes', 'woocommerce-megasoft-gateway-v2' ); ?></option>
                    </select>

                    <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Buscar...', 'woocommerce-megasoft-gateway-v2' ); ?>" />

                    <button type="submit" class="button"><?php esc_html_e( 'Filtrar', 'woocommerce-megasoft-gateway-v2' ); ?></button>

                    <?php if ( $status_filter || $search ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=megasoft-v2-transactions' ) ); ?>" class="button">
                            <?php esc_html_e( 'Limpiar', 'woocommerce-megasoft-gateway-v2' ); ?>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Transactions Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                        <th><?php esc_html_e( 'Orden', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                        <th><?php esc_html_e( 'Control', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                        <th><?php esc_html_e( 'Autorización', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                        <th><?php esc_html_e( 'Tipo', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                        <th><?php esc_html_e( 'Monto', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                        <th><?php esc_html_e( 'Tarjeta', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                        <th><?php esc_html_e( 'Estado', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                        <th><?php esc_html_e( 'Fecha', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                        <th><?php esc_html_e( 'Acciones', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $transactions ) ) : ?>
                        <?php foreach ( $transactions as $transaction ) : ?>
                            <tr>
                                <td><?php echo esc_html( $transaction->id ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $transaction->order_id . '&action=edit' ) ); ?>">
                                        #<?php echo esc_html( $transaction->order_id ); ?>
                                    </a>
                                </td>
                                <td><code><?php echo esc_html( $transaction->control_number ); ?></code></td>
                                <td><code><?php echo esc_html( $transaction->authorization_code ?: '-' ); ?></code></td>
                                <td><?php echo esc_html( $transaction->transaction_type ); ?></td>
                                <td><?php echo wc_price( $transaction->amount ); ?></td>
                                <td>
                                    <?php if ( $transaction->card_last_four ) : ?>
                                        <?php echo esc_html( $transaction->card_type ); ?> ****<?php echo esc_html( $transaction->card_last_four ); ?>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $this->get_status_badge( $transaction->status ); ?></td>
                                <td><?php echo esc_html( mysql2date( 'd/m/Y H:i', $transaction->created_at ) ); ?></td>
                                <td>
                                    <button type="button" class="button button-small megasoft-view-details" data-transaction-id="<?php echo esc_attr( $transaction->id ); ?>">
                                        <?php esc_html_e( 'Ver', 'woocommerce-megasoft-gateway-v2' ); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="10"><?php esc_html_e( 'No se encontraron transacciones', 'woocommerce-megasoft-gateway-v2' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ( $total_pages > 1 ) : ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links( array(
                            'base'      => add_query_arg( 'paged', '%#%' ),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $total_pages,
                            'current'   => $page,
                        ) );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render logs page
     */
    public function render_logs_page() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_logs';

        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

        if ( ! $table_exists ) {
            ?>
            <div class="wrap megasoft-v2-admin">
                <h1><?php esc_html_e( 'Logs del Sistema', 'woocommerce-megasoft-gateway-v2' ); ?></h1>
                <div class="notice notice-error">
                    <p>
                        <strong><?php esc_html_e( 'Error:', 'woocommerce-megasoft-gateway-v2' ); ?></strong>
                        <?php esc_html_e( 'La tabla de logs no existe. Por favor desactiva y reactiva el plugin para crear las tablas necesarias.', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </p>
                </div>
            </div>
            <?php
            return;
        }

        // Pagination
        $per_page = 100;
        $page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $offset = ( $page - 1 ) * $per_page;

        // Filters
        $level_filter = isset( $_GET['level'] ) ? sanitize_text_field( $_GET['level'] ) : '';

        // Build query
        $where_sql = '';
        if ( $level_filter ) {
            $where_sql = $wpdb->prepare( 'WHERE level = %s', $level_filter );
        }

        // Get total count
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name $where_sql" );
        $total_pages = ceil( $total / $per_page );

        // Get logs
        $logs = $wpdb->get_results(
            "SELECT * FROM $table_name $where_sql ORDER BY created_at DESC LIMIT $per_page OFFSET $offset"
        );

        ?>
        <div class="wrap megasoft-v2-admin">
            <h1>
                <?php esc_html_e( 'Logs del Sistema', 'woocommerce-megasoft-gateway-v2' ); ?>
                <button type="button" class="page-title-action" id="megasoft-clear-logs">
                    <?php esc_html_e( 'Limpiar Logs', 'woocommerce-megasoft-gateway-v2' ); ?>
                </button>
            </h1>

            <!-- Info -->
            <?php if ( ! $table_exists || $total === 0 ) : ?>
            <div class="notice notice-info" style="margin-top: 20px;">
                <p>
                    <strong><?php esc_html_e( 'Estado:', 'woocommerce-megasoft-gateway-v2' ); ?></strong>
                    <?php if ( ! $table_exists ) : ?>
                        <?php esc_html_e( 'La tabla de logs no existe. Reactiva el plugin para crearla.', 'woocommerce-megasoft-gateway-v2' ); ?>
                    <?php elseif ( $total === 0 ) : ?>
                        <?php esc_html_e( 'No hay logs registrados todavía.', 'woocommerce-megasoft-gateway-v2' ); ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="megasoft-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="megasoft-v2-logs" />

                    <select name="level">
                        <option value=""><?php esc_html_e( 'Todos los niveles', 'woocommerce-megasoft-gateway-v2' ); ?></option>
                        <option value="DEBUG" <?php selected( $level_filter, 'DEBUG' ); ?>>DEBUG</option>
                        <option value="INFO" <?php selected( $level_filter, 'INFO' ); ?>>INFO</option>
                        <option value="WARN" <?php selected( $level_filter, 'WARN' ); ?>>WARNING</option>
                        <option value="ERROR" <?php selected( $level_filter, 'ERROR' ); ?>>ERROR</option>
                    </select>

                    <button type="submit" class="button"><?php esc_html_e( 'Filtrar', 'woocommerce-megasoft-gateway-v2' ); ?></button>
                </form>
            </div>

            <!-- Logs Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 80px;"><?php esc_html_e( 'Nivel', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                        <th><?php esc_html_e( 'Mensaje', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                        <th style="width: 150px;"><?php esc_html_e( 'Fecha', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $logs ) ) : ?>
                        <?php foreach ( $logs as $log ) : ?>
                            <tr>
                                <td><?php echo $this->get_log_level_badge( $log->level ); ?></td>
                                <td>
                                    <strong><?php echo esc_html( $log->message ); ?></strong>
                                    <?php if ( ! empty( $log->context ) ) : ?>
                                        <br>
                                        <code style="display: block; margin-top: 5px; padding: 5px; background: #f5f5f5; font-size: 11px; overflow-x: auto;">
                                            <?php echo esc_html( $log->context ); ?>
                                        </code>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( mysql2date( 'd/m/Y H:i:s', $log->created_at ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="3"><?php esc_html_e( 'No hay logs', 'woocommerce-megasoft-gateway-v2' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ( $total_pages > 1 ) : ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links( array(
                            'base'      => add_query_arg( 'paged', '%#%' ),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $total_pages,
                            'current'   => $page,
                        ) );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render diagnostics page
     */
    public function render_diagnostics_page() {
        // Get gateway settings
        $gateway = new WC_Gateway_MegaSoft_V2();
        $api_user = $gateway->get_option( 'api_user' );
        $api_password = $gateway->get_option( 'api_password' );
        $cod_afiliacion = $gateway->get_option( 'cod_afiliacion' );
        $testmode = 'yes' === $gateway->get_option( 'testmode' );

        // Handle test connection request
        $test_results = null;
        if ( isset( $_POST['test_connection'] ) && check_admin_referer( 'megasoft_test_connection' ) ) {
            $test_results = MegaSoft_V2_Diagnostics::test_connection( $api_user, $api_password, $cod_afiliacion, $testmode );
        }

        // Handle clear logs request
        $clear_logs_result = null;
        if ( isset( $_POST['clear_logs'] ) && check_admin_referer( 'megasoft_clear_logs' ) ) {
            $days = isset( $_POST['days'] ) ? intval( $_POST['days'] ) : 30;
            $clear_logs_result = MegaSoft_V2_Diagnostics::clear_old_logs( $days );
        }

        // Get system info
        $system_info = MegaSoft_V2_Diagnostics::get_system_info();
        $logs_count = MegaSoft_V2_Diagnostics::get_logs_count();
        ?>
        <div class="wrap megasoft-v2-admin">
            <h1><?php esc_html_e( 'Diagnóstico del Sistema', 'woocommerce-megasoft-gateway-v2' ); ?></h1>

            <?php if ( $clear_logs_result ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html( $clear_logs_result['message'] ); ?></p>
                </div>
            <?php endif; ?>

            <!-- System Information -->
            <div class="megasoft-status-card">
                <h2><?php esc_html_e( 'Información del Sistema', 'woocommerce-megasoft-gateway-v2' ); ?></h2>
                <table class="widefat striped">
                    <tbody>
                        <tr>
                            <td><strong><?php esc_html_e( 'Versión del Plugin', 'woocommerce-megasoft-gateway-v2' ); ?></strong></td>
                            <td><?php echo esc_html( $system_info['plugin_version'] ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Versión de WordPress', 'woocommerce-megasoft-gateway-v2' ); ?></strong></td>
                            <td><?php echo esc_html( $system_info['wordpress_version'] ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Versión de WooCommerce', 'woocommerce-megasoft-gateway-v2' ); ?></strong></td>
                            <td><?php echo esc_html( $system_info['woocommerce_version'] ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Versión de PHP', 'woocommerce-megasoft-gateway-v2' ); ?></strong></td>
                            <td><?php echo esc_html( $system_info['php_version'] ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'SSL Activo', 'woocommerce-megasoft-gateway-v2' ); ?></strong></td>
                            <td>
                                <?php if ( $system_info['ssl_active'] ) : ?>
                                    <span style="color: green;">✓ <?php esc_html_e( 'Activo', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                                <?php else : ?>
                                    <span style="color: red;">✗ <?php esc_html_e( 'Inactivo (REQUERIDO)', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Versión de BD', 'woocommerce-megasoft-gateway-v2' ); ?></strong></td>
                            <td><?php echo esc_html( $system_info['db_version'] ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'URL del Webhook', 'woocommerce-megasoft-gateway-v2' ); ?></strong></td>
                            <td><code><?php echo esc_html( $system_info['webhook_url'] ); ?></code></td>
                        </tr>
                    </tbody>
                </table>

                <h3 style="margin-top: 20px;"><?php esc_html_e( 'Extensiones PHP', 'woocommerce-megasoft-gateway-v2' ); ?></h3>
                <table class="widefat striped">
                    <tbody>
                        <?php foreach ( $system_info['php_extensions'] as $ext => $loaded ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $ext ); ?></strong></td>
                                <td>
                                    <?php if ( $loaded ) : ?>
                                        <span style="color: green;">✓ <?php esc_html_e( 'Instalado', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                                    <?php else : ?>
                                        <span style="color: red;">✗ <?php esc_html_e( 'No instalado', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Connection Tester -->
            <div class="megasoft-status-card" style="margin-top: 20px;">
                <h2><?php esc_html_e( 'Probar Conexión con Mega Soft', 'woocommerce-megasoft-gateway-v2' ); ?></h2>

                <p><?php esc_html_e( 'Verifica que tus credenciales sean correctas y que el servidor pueda conectarse a Mega Soft.', 'woocommerce-megasoft-gateway-v2' ); ?></p>

                <form method="post" action="">
                    <?php wp_nonce_field( 'megasoft_test_connection' ); ?>
                    <p>
                        <strong><?php esc_html_e( 'Modo:', 'woocommerce-megasoft-gateway-v2' ); ?></strong>
                        <?php echo $testmode ? esc_html__( 'Pruebas (paytest.megasoft.com.ve)', 'woocommerce-megasoft-gateway-v2' ) : esc_html__( 'Producción (e-payment.megasoft.com.ve)', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </p>
                    <button type="submit" name="test_connection" class="button button-primary">
                        <?php esc_html_e( 'Probar Conexión', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </button>
                </form>

                <?php if ( $test_results ) : ?>
                    <div style="margin-top: 20px; padding: 15px; background: <?php echo $test_results['success'] ? '#d4edda' : '#f8d7da'; ?>; border-left: 4px solid <?php echo $test_results['success'] ? '#28a745' : '#dc3545'; ?>;">
                        <h3>
                            <?php if ( $test_results['success'] ) : ?>
                                <span style="color: #155724;">✓ <?php esc_html_e( 'Todas las pruebas pasaron', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                            <?php else : ?>
                                <span style="color: #721c24;">✗ <?php esc_html_e( 'Algunas pruebas fallaron', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                            <?php endif; ?>
                        </h3>

                        <?php foreach ( $test_results['tests'] as $test_name => $test ) : ?>
                            <div style="margin: 10px 0; padding: 10px; background: white; border-radius: 4px;">
                                <strong>
                                    <?php if ( $test['passed'] ) : ?>
                                        <span style="color: green;">✓</span>
                                    <?php else : ?>
                                        <span style="color: red;">✗</span>
                                    <?php endif; ?>
                                    <?php echo esc_html( ucfirst( $test_name ) ); ?>:
                                </strong>
                                <?php echo esc_html( $test['message'] ); ?>

                                <?php if ( isset( $test['details'] ) ) : ?>
                                    <details style="margin-top: 5px;">
                                        <summary style="cursor: pointer; color: #0073aa;"><?php esc_html_e( 'Ver detalles', 'woocommerce-megasoft-gateway-v2' ); ?></summary>
                                        <pre style="background: #f5f5f5; padding: 10px; margin-top: 5px; overflow-x: auto;"><?php print_r( $test['details'] ); ?></pre>
                                    </details>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Logs Management -->
            <div class="megasoft-status-card" style="margin-top: 20px;">
                <h2><?php esc_html_e( 'Gestión de Logs', 'woocommerce-megasoft-gateway-v2' ); ?></h2>

                <p>
                    <strong><?php esc_html_e( 'Total de logs:', 'woocommerce-megasoft-gateway-v2' ); ?></strong>
                    <?php echo esc_html( $logs_count['total'] ); ?>
                </p>

                <?php if ( ! empty( $logs_count['by_level'] ) ) : ?>
                    <ul>
                        <?php foreach ( $logs_count['by_level'] as $level => $count ) : ?>
                            <li>
                                <span class="log-level log-level-<?php echo esc_attr( strtolower( $level ) ); ?>">
                                    <?php echo esc_html( strtoupper( $level ) ); ?>
                                </span>
                                : <?php echo esc_html( $count ); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <form method="post" action="" style="margin-top: 15px;">
                    <?php wp_nonce_field( 'megasoft_clear_logs' ); ?>
                    <label>
                        <?php esc_html_e( 'Eliminar logs más antiguos de:', 'woocommerce-megasoft-gateway-v2' ); ?>
                        <select name="days">
                            <option value="7">7 días</option>
                            <option value="30" selected>30 días</option>
                            <option value="60">60 días</option>
                            <option value="90">90 días</option>
                        </select>
                    </label>
                    <button type="submit" name="clear_logs" class="button" onclick="return confirm('<?php esc_attr_e( '¿Estás seguro de eliminar logs antiguos?', 'woocommerce-megasoft-gateway-v2' ); ?>');">
                        <?php esc_html_e( 'Limpiar Logs Antiguos', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </button>
                </form>

                <p style="margin-top: 15px;">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=megasoft-v2-logs' ) ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Ver Todos los Logs', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </a>
                </p>
            </div>

            <!-- Required Credentials Info -->
            <div class="megasoft-status-card" style="margin-top: 20px;">
                <h2><?php esc_html_e( 'Credenciales Requeridas', 'woocommerce-megasoft-gateway-v2' ); ?></h2>

                <p><?php esc_html_e( 'Según la documentación oficial de Mega Soft API v4.24, se requieren únicamente 3 credenciales:', 'woocommerce-megasoft-gateway-v2' ); ?></p>

                <ol>
                    <li>
                        <strong><?php esc_html_e( 'Usuario API:', 'woocommerce-megasoft-gateway-v2' ); ?></strong>
                        <?php esc_html_e( 'Usuario para autenticación Basic Auth', 'woocommerce-megasoft-gateway-v2' ); ?>
                        <?php if ( ! empty( $api_user ) ) : ?>
                            <span style="color: green;">✓ <?php esc_html_e( 'Configurado', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                        <?php else : ?>
                            <span style="color: red;">✗ <?php esc_html_e( 'No configurado', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Contraseña API:', 'woocommerce-megasoft-gateway-v2' ); ?></strong>
                        <?php esc_html_e( 'Contraseña para autenticación Basic Auth', 'woocommerce-megasoft-gateway-v2' ); ?>
                        <?php if ( ! empty( $api_password ) ) : ?>
                            <span style="color: green;">✓ <?php esc_html_e( 'Configurado', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                        <?php else : ?>
                            <span style="color: red;">✗ <?php esc_html_e( 'No configurado', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Código de Afiliación:', 'woocommerce-megasoft-gateway-v2' ); ?></strong>
                        <?php esc_html_e( 'Código numérico enviado en las peticiones XML (ej: 1234567)', 'woocommerce-megasoft-gateway-v2' ); ?>
                        <?php if ( ! empty( $cod_afiliacion ) ) : ?>
                            <span style="color: green;">✓ <?php esc_html_e( 'Configurado', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                        <?php else : ?>
                            <span style="color: red;">✗ <?php esc_html_e( 'No configurado', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                        <?php endif; ?>
                    </li>
                </ol>

                <p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=megasoft_v2' ) ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Configurar Credenciales', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap megasoft-v2-admin">
            <h1><?php esc_html_e( 'Configuración de Mega Soft Gateway', 'woocommerce-megasoft-gateway-v2' ); ?></h1>

            <p>
                <?php
                printf(
                    __( 'Configura el gateway desde la <a href="%s">página de configuración de WooCommerce</a>.', 'woocommerce-megasoft-gateway-v2' ),
                    admin_url( 'admin.php?page=wc-settings&tab=checkout&section=megasoft_v2' )
                );
                ?>
            </p>

            <div class="megasoft-settings-cards">
                <div class="megasoft-settings-card">
                    <h2><?php esc_html_e( 'Gateway Principal', 'woocommerce-megasoft-gateway-v2' ); ?></h2>
                    <p><?php esc_html_e( 'Configura tarjetas de crédito y débito (captura directa)', 'woocommerce-megasoft-gateway-v2' ); ?></p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=megasoft_v2' ) ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Configurar', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </a>
                </div>

                <div class="megasoft-settings-card">
                    <h2><?php esc_html_e( 'Pago Móvil C2P', 'woocommerce-megasoft-gateway-v2' ); ?></h2>
                    <p><?php esc_html_e( 'Configura Pago Móvil Cliente a Persona', 'woocommerce-megasoft-gateway-v2' ); ?></p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=megasoft_pago_movil_c2p' ) ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Configurar', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </a>
                </div>

                <div class="megasoft-settings-card">
                    <h2><?php esc_html_e( 'Pago Móvil P2C', 'woocommerce-megasoft-gateway-v2' ); ?></h2>
                    <p><?php esc_html_e( 'Configura Pago Móvil Persona a Cliente', 'woocommerce-megasoft-gateway-v2' ); ?></p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=megasoft_pago_movil_p2c' ) ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Configurar', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </a>
                </div>
            </div>

            <!-- Documentation -->
            <div class="megasoft-documentation">
                <h2><?php esc_html_e( 'Documentación', 'woocommerce-megasoft-gateway-v2' ); ?></h2>

                <h3><?php esc_html_e( 'Configuración Inicial', 'woocommerce-megasoft-gateway-v2' ); ?></h3>
                <ol>
                    <li><?php esc_html_e( 'Asegúrate de tener certificado SSL activo (HTTPS)', 'woocommerce-megasoft-gateway-v2' ); ?></li>
                    <li><?php esc_html_e( 'Obtén tus credenciales de Mega Soft (Usuario API, Contraseña, Merchant ID, Terminal ID)', 'woocommerce-megasoft-gateway-v2' ); ?></li>
                    <li><?php esc_html_e( 'Configura cada método de pago que desees activar', 'woocommerce-megasoft-gateway-v2' ); ?></li>
                    <li><?php esc_html_e( 'Registra la URL del webhook en el panel de Mega Soft', 'woocommerce-megasoft-gateway-v2' ); ?></li>
                    <li><?php esc_html_e( 'Realiza pruebas en modo de prueba antes de activar producción', 'woocommerce-megasoft-gateway-v2' ); ?></li>
                </ol>

                <h3><?php esc_html_e( 'URL del Webhook', 'woocommerce-megasoft-gateway-v2' ); ?></h3>
                <p>
                    <?php esc_html_e( 'Registra esta URL en el panel de Mega Soft para recibir notificaciones:', 'woocommerce-megasoft-gateway-v2' ); ?>
                </p>
                <p>
                    <code style="padding: 10px; background: #f5f5f5; display: inline-block;">
                        <?php echo esc_url( MegaSoft_V2_Webhook::get_webhook_url() ); ?>
                    </code>
                </p>

                <h3><?php esc_html_e( 'Soporte', 'woocommerce-megasoft-gateway-v2' ); ?></h3>
                <p><?php esc_html_e( 'Para soporte técnico, contacta a Mega Soft Computación C.A.', 'woocommerce-megasoft-gateway-v2' ); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Add meta boxes to order page
     */
    public function add_order_meta_boxes() {
        add_meta_box(
            'megasoft-v2-transaction-details',
            __( 'Detalles de Transacción Mega Soft', 'woocommerce-megasoft-gateway-v2' ),
            array( $this, 'render_order_meta_box' ),
            'shop_order',
            'side',
            'high'
        );
    }

    /**
     * Render order meta box
     */
    public function render_order_meta_box( $post ) {
        $order = wc_get_order( $post->ID );

        if ( ! $order || strpos( $order->get_payment_method(), 'megasoft' ) === false ) {
            echo '<p>' . esc_html__( 'Esta orden no fue procesada con Mega Soft.', 'woocommerce-megasoft-gateway-v2' ) . '</p>';
            return;
        }

        $control = $order->get_meta( '_megasoft_v2_control' );
        $authorization = $order->get_meta( '_megasoft_v2_authorization' );
        $card_last_four = $order->get_meta( '_megasoft_v2_card_last_four' );
        $card_type = $order->get_meta( '_megasoft_v2_card_type' );

        ?>
        <div class="megasoft-order-details">
            <?php if ( $control ) : ?>
                <p>
                    <strong><?php esc_html_e( 'Número de Control:', 'woocommerce-megasoft-gateway-v2' ); ?></strong><br>
                    <code><?php echo esc_html( $control ); ?></code>
                </p>
            <?php endif; ?>

            <?php if ( $authorization ) : ?>
                <p>
                    <strong><?php esc_html_e( 'Código de Autorización:', 'woocommerce-megasoft-gateway-v2' ); ?></strong><br>
                    <code><?php echo esc_html( $authorization ); ?></code>
                </p>
            <?php endif; ?>

            <?php if ( $card_last_four ) : ?>
                <p>
                    <strong><?php esc_html_e( 'Tarjeta:', 'woocommerce-megasoft-gateway-v2' ); ?></strong><br>
                    <?php echo esc_html( ucfirst( $card_type ) ); ?> ****<?php echo esc_html( $card_last_four ); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Add columns to orders list
     */
    public function add_order_columns( $columns ) {
        $new_columns = array();

        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;

            if ( $key === 'order_status' ) {
                $new_columns['megasoft_control'] = __( 'Control Mega Soft', 'woocommerce-megasoft-gateway-v2' );
            }
        }

        return $new_columns;
    }

    /**
     * Render custom order column
     */
    public function render_order_column( $column, $post_id ) {
        if ( $column === 'megasoft_control' ) {
            $order = wc_get_order( $post_id );

            if ( $order && strpos( $order->get_payment_method(), 'megasoft' ) !== false ) {
                $control = $order->get_meta( '_megasoft_v2_control' );
                if ( $control ) {
                    echo '<code>' . esc_html( $control ) . '</code>';
                }
            }
        }
    }

    /**
     * Add plugin action links
     */
    public function add_plugin_action_links( $links ) {
        $settings_link = '<a href="' . admin_url( 'admin.php?page=megasoft-v2-dashboard' ) . '">' . __( 'Dashboard', 'woocommerce-megasoft-gateway-v2' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_transactions';

        $stats = array(
            'total_transactions'    => $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ),
            'approved_transactions' => $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'approved'" ),
            'failed_transactions'   => $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status IN ('failed', 'declined')" ),
            'total_amount'          => $wpdb->get_var( "SELECT SUM(amount) FROM $table_name WHERE status = 'approved'" ),
        );

        return $stats;
    }

    /**
     * Get recent transactions
     */
    private function get_recent_transactions( $limit = 10 ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_transactions';

        return $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT " . intval( $limit )
        );
    }

    /**
     * Get transactions chart data (last 7 days)
     */
    private function get_transactions_chart_data() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_transactions';
        $data = array();

        for ( $i = 6; $i >= 0; $i-- ) {
            $date = date( 'Y-m-d', strtotime( "-$i days" ) );

            $count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s",
                $date
            ) );

            $data[] = array(
                'date'  => date( 'd/m', strtotime( $date ) ),
                'count' => intval( $count ),
            );
        }

        return $data;
    }

    /**
     * Get status badge HTML
     */
    private function get_status_badge( $status ) {
        $badges = array(
            'approved' => '<span class="megasoft-badge success">Aprobada</span>',
            'failed'   => '<span class="megasoft-badge error">Rechazada</span>',
            'pending'  => '<span class="megasoft-badge warning">Pendiente</span>',
            'declined' => '<span class="megasoft-badge error">Declinada</span>',
        );

        return $badges[ $status ] ?? '<span class="megasoft-badge">' . esc_html( $status ) . '</span>';
    }

    /**
     * Get log level badge HTML
     */
    private function get_log_level_badge( $level ) {
        $badges = array(
            'DEBUG' => '<span class="megasoft-badge">DEBUG</span>',
            'INFO'  => '<span class="megasoft-badge success">INFO</span>',
            'WARN'  => '<span class="megasoft-badge warning">WARN</span>',
            'ERROR' => '<span class="megasoft-badge error">ERROR</span>',
        );

        return $badges[ $level ] ?? '<span class="megasoft-badge">' . esc_html( $level ) . '</span>';
    }

    /**
     * AJAX: Export transactions to CSV
     */
    public function ajax_export_transactions() {
        check_ajax_referer( 'megasoft_v2_admin', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'megasoft_v2_transactions';

        $transactions = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A );

        if ( empty( $transactions ) ) {
            wp_send_json_error( 'No hay transacciones para exportar' );
        }

        // Generate CSV
        $filename = 'megasoft-transactions-' . date( 'Y-m-d' ) . '.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        $output = fopen( 'php://output', 'w' );

        // Headers
        fputcsv( $output, array_keys( $transactions[0] ) );

        // Data
        foreach ( $transactions as $transaction ) {
            fputcsv( $output, $transaction );
        }

        fclose( $output );
        exit;
    }

    /**
     * AJAX: Get statistics
     */
    public function ajax_get_stats() {
        check_ajax_referer( 'megasoft_v2_admin', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $stats = $this->get_dashboard_stats();
        wp_send_json_success( $stats );
    }

    /**
     * AJAX: Clear logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer( 'megasoft_v2_admin', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'megasoft_v2_logs';

        $deleted = $wpdb->query( "DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)" );

        wp_send_json_success( array(
            'message' => sprintf( __( '%d logs eliminados', 'woocommerce-megasoft-gateway-v2' ), $deleted ),
        ) );
    }
}
