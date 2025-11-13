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
        add_action( 'wp_ajax_megasoft_v2_get_transaction_details', array( $this, 'ajax_get_transaction_details' ) );

        // Add meta boxes to order page
        add_action( 'add_meta_boxes', array( $this, 'add_order_meta_boxes' ) );

        // Add columns to orders list (compatible with HPOS and legacy)
        add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_order_columns' ) ); // HPOS
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_columns' ) ); // Legacy
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_order_column' ), 10, 2 ); // HPOS
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_column' ), 10, 2 ); // Legacy

        // Add voucher download handler
        add_action( 'admin_init', array( $this, 'handle_voucher_download' ) );

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
        $recent_transactions = $this->get_recent_transactions( 5 );

        ?>
        <div class="wrap megasoft-v2-admin">
            <h1><?php esc_html_e( 'Mega Soft Gateway', 'woocommerce-megasoft-gateway-v2' ); ?></h1>

            <!-- Stats Cards -->
            <div class="megasoft-stats-grid">
                <div class="stat-card">
                    <h3><?php esc_html_e( 'Total Transacciones', 'woocommerce-megasoft-gateway-v2' ); ?></h3>
                    <div class="stat-number"><?php echo esc_html( $stats['total_transactions'] ); ?></div>
                </div>

                <div class="stat-card success">
                    <h3><?php esc_html_e( 'Aprobadas', 'woocommerce-megasoft-gateway-v2' ); ?></h3>
                    <div class="stat-number"><?php echo esc_html( $stats['approved_transactions'] ); ?></div>
                    <?php if ( $stats['total_transactions'] > 0 ) : ?>
                        <div class="stat-percentage">
                            <?php echo number_format( ( $stats['approved_transactions'] / $stats['total_transactions'] ) * 100, 1 ); ?>% del total
                        </div>
                    <?php endif; ?>
                </div>

                <div class="stat-card error">
                    <h3><?php esc_html_e( 'Rechazadas', 'woocommerce-megasoft-gateway-v2' ); ?></h3>
                    <div class="stat-number"><?php echo esc_html( $stats['failed_transactions'] ); ?></div>
                    <?php if ( $stats['total_transactions'] > 0 ) : ?>
                        <div class="stat-percentage">
                            <?php echo number_format( ( $stats['failed_transactions'] / $stats['total_transactions'] ) * 100, 1 ); ?>% del total
                        </div>
                    <?php endif; ?>
                </div>

                <div class="stat-card" style="border-top-color: #2271b1;">
                    <h3><?php esc_html_e( 'Total Procesado', 'woocommerce-megasoft-gateway-v2' ); ?></h3>
                    <div class="stat-amount"><?php echo wc_price( $stats['total_amount'] ); ?></div>
                </div>
            </div>

            <!-- Charts in Grid -->
            <div class="megasoft-charts">
                <div class="chart-container">
                    <h3><?php esc_html_e( 'Transacciones (Últimos 7 días)', 'woocommerce-megasoft-gateway-v2' ); ?></h3>
                    <div style="position: relative; height: 250px;">
                        <canvas id="megasoft-transactions-chart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <h3><?php esc_html_e( 'Tasa de Aprobación', 'woocommerce-megasoft-gateway-v2' ); ?></h3>
                    <div style="position: relative; height: 250px;">
                        <canvas id="megasoft-approval-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="megasoft-recent-transactions">
                <h2><?php esc_html_e( 'Transacciones Recientes', 'woocommerce-megasoft-gateway-v2' ); ?></h2>
                <?php if ( ! empty( $recent_transactions ) ) : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Orden', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                                <th><?php esc_html_e( 'Control', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                                <th><?php esc_html_e( 'Monto', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                                <th><?php esc_html_e( 'Tarjeta', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                                <th><?php esc_html_e( 'Estado', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                                <th><?php esc_html_e( 'Fecha', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $recent_transactions as $transaction ) : ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $transaction->order_id . '&action=edit' ) ); ?>">
                                            #<?php echo esc_html( $transaction->order_id ); ?>
                                        </a>
                                    </td>
                                    <td><code style="font-size: 11px;"><?php echo esc_html( $transaction->control_number ); ?></code></td>
                                    <td><strong><?php echo wc_price( $transaction->amount ); ?></strong></td>
                                    <td>
                                        <?php if ( $transaction->card_last_four ) : ?>
                                            <span style="text-transform: capitalize;"><?php echo esc_html( $transaction->card_type ); ?></span>
                                            ••••<?php echo esc_html( $transaction->card_last_four ); ?>
                                        <?php else : ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $this->get_status_badge( $transaction->status ); ?></td>
                                    <td><?php echo esc_html( mysql2date( 'd/m/Y H:i', $transaction->created_at ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p style="margin-top: 15px;">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=megasoft-v2-transactions' ) ); ?>" class="button">
                            <?php esc_html_e( 'Ver Todas las Transacciones', 'woocommerce-megasoft-gateway-v2' ); ?>
                        </a>
                    </p>
                <?php else : ?>
                    <div class="notice notice-info inline">
                        <p><?php esc_html_e( 'No hay transacciones todavía. Las transacciones aparecerán aquí después del primer pago.', 'woocommerce-megasoft-gateway-v2' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Links -->
            <div class="megasoft-quick-actions" style="margin-top: 20px;">
                <h2><?php esc_html_e( 'Accesos Rápidos', 'woocommerce-megasoft-gateway-v2' ); ?></h2>
                <div class="action-buttons">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=megasoft-v2-transactions' ) ); ?>" class="button">
                        <?php esc_html_e( 'Ver Transacciones', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=megasoft-v2-logs' ) ); ?>" class="button">
                        <?php esc_html_e( 'Ver Logs', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=megasoft-v2-diagnostics' ) ); ?>" class="button">
                        <?php esc_html_e( 'Diagnóstico', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=megasoft_v2' ) ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Configuración', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </a>
                </div>
            </div>

            <script>
                jQuery(document).ready(function($) {
                    // Transactions chart data
                    const transactionsData = <?php echo json_encode( $this->get_transactions_chart_data() ); ?>;

                    // Approval rate data
                    const approvalData = {
                        approved: <?php echo intval( $stats['approved_transactions'] ); ?>,
                        failed: <?php echo intval( $stats['failed_transactions'] ); ?>
                    };

                    // Only render charts if we have Chart.js loaded
                    if (typeof Chart !== 'undefined') {
                        // Transactions Line Chart
                        const transactionsCtx = document.getElementById('megasoft-transactions-chart');
                        if (transactionsCtx) {
                            new Chart(transactionsCtx, {
                                type: 'line',
                                data: {
                                    labels: transactionsData.map(d => d.date),
                                    datasets: [{
                                        label: '<?php esc_html_e( 'Transacciones', 'woocommerce-megasoft-gateway-v2' ); ?>',
                                        data: transactionsData.map(d => d.count),
                                        borderColor: '#2271b1',
                                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                                        tension: 0.4,
                                        fill: true
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                stepSize: 1
                                            }
                                        }
                                    }
                                }
                            });
                        }

                        // Approval Rate Doughnut Chart
                        const approvalCtx = document.getElementById('megasoft-approval-chart');
                        if (approvalCtx && (approvalData.approved > 0 || approvalData.failed > 0)) {
                            new Chart(approvalCtx, {
                                type: 'doughnut',
                                data: {
                                    labels: [
                                        '<?php esc_html_e( 'Aprobadas', 'woocommerce-megasoft-gateway-v2' ); ?>',
                                        '<?php esc_html_e( 'Rechazadas', 'woocommerce-megasoft-gateway-v2' ); ?>'
                                    ],
                                    datasets: [{
                                        data: [approvalData.approved, approvalData.failed],
                                        backgroundColor: ['#00a32a', '#d63638'],
                                        borderWidth: 2,
                                        borderColor: '#fff'
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'bottom'
                                        }
                                    }
                                }
                            });
                        } else if (approvalCtx) {
                            // Show message if no data
                            approvalCtx.parentElement.innerHTML = '<p style="text-align: center; color: #666; padding: 60px 20px;"><?php esc_html_e( 'No hay datos suficientes todavía', 'woocommerce-megasoft-gateway-v2' ); ?></p>';
                        }
                    }
                });
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
                            <?php
                            // Get order once for efficiency
                            $order = wc_get_order( $transaction->order_id );
                            $voucher_html = $order ? $order->get_meta( '_megasoft_voucher_html' ) : '';
                            ?>
                            <tr>
                                <td><?php echo esc_html( $transaction->id ); ?></td>
                                <td>
                                    <?php
                                    if ( $order ) {
                                        $edit_url = $order->get_edit_order_url();
                                        ?>
                                        <a href="<?php echo esc_url( $edit_url ); ?>">
                                            #<?php echo esc_html( $transaction->order_id ); ?>
                                        </a>
                                        <?php
                                    } else {
                                        echo '#' . esc_html( $transaction->order_id );
                                    }
                                    ?>
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
                                    <?php if ( $voucher_html ) : ?>
                                        <?php
                                        $download_url = add_query_arg( array(
                                            'action'   => 'megasoft_download_voucher',
                                            'order_id' => $transaction->order_id,
                                            'nonce'    => wp_create_nonce( 'megasoft_voucher_' . $transaction->order_id ),
                                        ), admin_url( 'admin.php' ) );
                                        ?>
                                        <a href="<?php echo esc_url( $download_url ); ?>" class="button button-small" title="<?php esc_attr_e( 'Descargar Voucher', 'woocommerce-megasoft-gateway-v2' ); ?>">
                                            <span class="dashicons dashicons-download" style="line-height: 1.4;"></span>
                                        </a>
                                    <?php else : ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>

                                    <button type="button" class="button button-small megasoft-view-details" data-transaction-id="<?php echo esc_attr( $transaction->id ); ?>" style="margin-left: 4px;">
                                        <span class="dashicons dashicons-visibility" style="line-height: 1.4;"></span>
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

            <!-- Transaction Details Sidebar -->
            <div id="megasoft-transaction-sidebar" class="megasoft-sidebar">
                <div class="megasoft-sidebar-overlay"></div>
                <div class="megasoft-sidebar-panel">
                    <div class="megasoft-sidebar-header">
                        <h2><?php esc_html_e( 'Detalles de Transacción', 'woocommerce-megasoft-gateway-v2' ); ?></h2>
                        <button class="megasoft-sidebar-close" type="button">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    <div class="megasoft-sidebar-body">
                        <div class="megasoft-loading" style="text-align: center; padding: 40px;">
                            <span class="spinner is-active" style="float: none;"></span>
                            <p><?php esc_html_e( 'Cargando detalles...', 'woocommerce-megasoft-gateway-v2' ); ?></p>
                        </div>
                        <div class="megasoft-details-content" style="display: none;"></div>
                    </div>
                </div>
            </div>
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
                    <?php esc_html_e( 'Limpiar Logs BD', 'woocommerce-megasoft-gateway-v2' ); ?>
                </button>
            </h1>

            <!-- ARCHIVO DE DEBUG (NUEVO SISTEMA) -->
            <?php
            $debug_file = MEGASOFT_V2_PLUGIN_PATH . 'megasoft-debug.log';
            $old_debug_file = MEGASOFT_V2_PLUGIN_PATH . 'debug-validation.log';
            $file_to_show = file_exists( $debug_file ) ? $debug_file : ( file_exists( $old_debug_file ) ? $old_debug_file : null );

            if ( $file_to_show ) :
            ?>
            <div style="margin-top: 20px; margin-bottom: 30px;">
                <h2>
                    <?php esc_html_e( 'Log de Debug (Archivo)', 'woocommerce-megasoft-gateway-v2' ); ?>
                    <a href="?page=megasoft-v2-logs&clear_debug=1" class="button button-small" style="margin-left: 10px;" onclick="return confirm('¿Eliminar archivo de debug?');">
                        <?php esc_html_e( 'Limpiar', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </a>
                </h2>

                <?php
                // Handle clear debug action
                if ( isset( $_GET['clear_debug'] ) && $_GET['clear_debug'] === '1' ) {
                    @unlink( $debug_file );
                    @unlink( $old_debug_file );
                    echo '<div class="notice notice-success"><p>' . esc_html__( 'Archivos de debug eliminados.', 'woocommerce-megasoft-gateway-v2' ) . '</p></div>';
                    echo '<script>window.location.href="?page=megasoft-v2-logs";</script>';
                } else {
                ?>

                <div class="notice notice-info inline" style="margin: 10px 0;">
                    <p>
                        <strong><?php esc_html_e( 'Ubicación:', 'woocommerce-megasoft-gateway-v2' ); ?></strong> <?php echo esc_html( $file_to_show ); ?>
                        | <strong><?php esc_html_e( 'Tamaño:', 'woocommerce-megasoft-gateway-v2' ); ?></strong> <?php echo size_format( filesize( $file_to_show ) ); ?>
                        | <strong><?php esc_html_e( 'Modificado:', 'woocommerce-megasoft-gateway-v2' ); ?></strong> <?php echo date_i18n( 'd/m/Y H:i:s', filemtime( $file_to_show ) ); ?>
                    </p>
                </div>

                <div style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px; overflow-x: auto; max-height: 400px; overflow-y: auto;">
                    <pre style="margin: 0; font-family: 'Courier New', monospace; font-size: 11px; line-height: 1.4; white-space: pre-wrap;"><?php echo esc_html( file_get_contents( $file_to_show ) ); ?></pre>
                </div>

                <?php } ?>
            </div>
            <?php else : ?>
            <div style="margin-top: 20px; margin-bottom: 30px;">
                <div class="notice notice-info">
                    <p><?php esc_html_e( 'No hay archivo de debug todavía. El archivo se creará automáticamente cuando ocurra la próxima transacción.', 'woocommerce-megasoft-gateway-v2' ); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- LOGS DE BASE DE DATOS -->
            <h2 style="margin-top: 30px;"><?php esc_html_e( 'Logs de Base de Datos', 'woocommerce-megasoft-gateway-v2' ); ?></h2>

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

        // Handle reinstall tables request
        $reinstall_result = null;
        if ( isset( $_POST['reinstall_tables'] ) && check_admin_referer( 'megasoft_reinstall_tables' ) ) {
            $reinstall_result = MegaSoft_V2_Installer::install();
            $reinstall_result['message'] = 'Tablas reinstaladas correctamente';
        }

        // Handle migrate vouchers request
        $migrate_result = null;
        if ( isset( $_POST['migrate_vouchers'] ) && check_admin_referer( 'megasoft_migrate_vouchers' ) ) {
            $migrate_result = MegaSoft_V2_Diagnostics::migrate_vouchers();
        }

        // Get system info
        $system_info = MegaSoft_V2_Diagnostics::get_system_info();
        $logs_count = MegaSoft_V2_Diagnostics::get_logs_count();
        $table_stats = MegaSoft_V2_Diagnostics::get_table_stats();
        ?>
        <div class="wrap megasoft-v2-admin">
            <h1><?php esc_html_e( 'Diagnóstico del Sistema', 'woocommerce-megasoft-gateway-v2' ); ?></h1>

            <?php if ( $clear_logs_result ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html( $clear_logs_result['message'] ); ?></p>
                </div>
            <?php endif; ?>

            <?php if ( $reinstall_result ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php echo esc_html( $reinstall_result['message'] ); ?></strong></p>
                    <p>Verificación: <?php print_r( $reinstall_result['verification'] ); ?></p>
                </div>
            <?php endif; ?>

            <?php if ( $migrate_result ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php echo esc_html( $migrate_result['message'] ); ?></strong></p>
                    <?php if ( $migrate_result['migrated'] > 0 ) : ?>
                        <p><?php printf( __( 'Se migraron %d vouchers de %d encontrados', 'woocommerce-megasoft-gateway-v2' ), $migrate_result['migrated'], $migrate_result['total'] ); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Database Statistics -->
            <div class="megasoft-status-card">
                <h2><?php esc_html_e( 'Estadísticas de Base de Datos', 'woocommerce-megasoft-gateway-v2' ); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Tabla', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                            <th><?php esc_html_e( 'Estado', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                            <th><?php esc_html_e( 'Total de Registros', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                            <th><?php esc_html_e( 'Detalles', 'woocommerce-megasoft-gateway-v2' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php esc_html_e( 'Transacciones', 'woocommerce-megasoft-gateway-v2' ); ?></strong></td>
                            <td>
                                <?php if ( $table_stats['transactions']['exists'] ) : ?>
                                    <span style="color: green;">✓ <?php esc_html_e( 'Existe', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                                <?php else : ?>
                                    <span style="color: red;">✗ <?php esc_html_e( 'No existe', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo esc_html( $table_stats['transactions']['total'] ); ?></strong></td>
                            <td>
                                <?php if ( ! empty( $table_stats['transactions']['by_status'] ) ) : ?>
                                    <?php foreach ( $table_stats['transactions']['by_status'] as $status => $count ) : ?>
                                        <span style="margin-right: 10px;">
                                            <strong><?php echo esc_html( ucfirst( $status ) ); ?>:</strong> <?php echo esc_html( $count ); ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <em><?php esc_html_e( 'Sin datos', 'woocommerce-megasoft-gateway-v2' ); ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Logs', 'woocommerce-megasoft-gateway-v2' ); ?></strong></td>
                            <td>
                                <?php if ( $table_stats['logs']['exists'] ) : ?>
                                    <span style="color: green;">✓ <?php esc_html_e( 'Existe', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                                <?php else : ?>
                                    <span style="color: red;">✗ <?php esc_html_e( 'No existe', 'woocommerce-megasoft-gateway-v2' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo esc_html( $table_stats['logs']['total'] ); ?></strong></td>
                            <td>
                                <?php if ( ! empty( $table_stats['logs']['by_level'] ) ) : ?>
                                    <?php foreach ( $table_stats['logs']['by_level'] as $level => $count ) : ?>
                                        <span style="margin-right: 10px;">
                                            <strong><?php echo esc_html( strtoupper( $level ) ); ?>:</strong> <?php echo esc_html( $count ); ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <em><?php esc_html_e( 'Sin datos', 'woocommerce-megasoft-gateway-v2' ); ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p style="margin-top: 15px;">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=megasoft-v2-transactions' ) ); ?>" class="button">
                        <?php esc_html_e( 'Ver Transacciones', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=megasoft-v2-logs' ) ); ?>" class="button">
                        <?php esc_html_e( 'Ver Logs', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </a>
                </p>

                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
                    <h3 style="margin-top: 0;"><?php esc_html_e( '🔧 Herramienta de Reparación', 'woocommerce-megasoft-gateway-v2' ); ?></h3>
                    <p><?php esc_html_e( 'Si las tablas no existen o los datos no se están guardando, usa este botón para reinstalar las tablas de la base de datos:', 'woocommerce-megasoft-gateway-v2' ); ?></p>
                    <form method="post" action="" style="margin-top: 10px;">
                        <?php wp_nonce_field( 'megasoft_reinstall_tables' ); ?>
                        <button type="submit" name="reinstall_tables" class="button button-primary" onclick="return confirm('¿Estás seguro de reinstalar las tablas? Esta acción es segura y no borrará datos existentes.');">
                            <?php esc_html_e( 'Reinstalar Tablas de Base de Datos', 'woocommerce-megasoft-gateway-v2' ); ?>
                        </button>
                    </form>
                </div>
            </div>

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

            <!-- Maintenance Tools -->
            <div class="megasoft-status-card" style="margin-top: 20px;">
                <h2><?php esc_html_e( 'Herramientas de Mantenimiento', 'woocommerce-megasoft-gateway-v2' ); ?></h2>

                <div style="margin-bottom: 20px;">
                    <h3><?php esc_html_e( 'Migración de Vouchers', 'woocommerce-megasoft-gateway-v2' ); ?></h3>
                    <p><?php esc_html_e( 'Si actualizaste de una versión anterior y los vouchers no se muestran en los pedidos, ejecuta esta migración para convertir los vouchers al nuevo formato compatible con HPOS.', 'woocommerce-megasoft-gateway-v2' ); ?></p>
                    <form method="post" action="" style="display: inline;">
                        <?php wp_nonce_field( 'megasoft_migrate_vouchers' ); ?>
                        <button type="submit" name="migrate_vouchers" class="button" onclick="return confirm('<?php esc_attr_e( '¿Deseas migrar los vouchers antiguos al nuevo formato?', 'woocommerce-megasoft-gateway-v2' ); ?>');">
                            <?php esc_html_e( 'Migrar Vouchers', 'woocommerce-megasoft-gateway-v2' ); ?>
                        </button>
                    </form>
                </div>

                <div style="margin-bottom: 20px;">
                    <h3><?php esc_html_e( 'Reinstalar Tablas de Base de Datos', 'woocommerce-megasoft-gateway-v2' ); ?></h3>
                    <p><?php esc_html_e( 'Si las tablas de base de datos están corruptas o faltan, puedes reinstalarlas aquí.', 'woocommerce-megasoft-gateway-v2' ); ?></p>
                    <form method="post" action="" style="display: inline;">
                        <?php wp_nonce_field( 'megasoft_reinstall_tables' ); ?>
                        <button type="submit" name="reinstall_tables" class="button" onclick="return confirm('<?php esc_attr_e( '¿Estás seguro de reinstalar las tablas?', 'woocommerce-megasoft-gateway-v2' ); ?>');">
                            <?php esc_html_e( 'Reinstalar Tablas', 'woocommerce-megasoft-gateway-v2' ); ?>
                        </button>
                    </form>
                </div>
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
        $screen = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        add_meta_box(
            'megasoft-v2-transaction-details',
            __( 'Detalles de Transacción Mega Soft', 'woocommerce-megasoft-gateway-v2' ),
            array( $this, 'render_order_meta_box' ),
            $screen,
            'side',
            'high'
        );
    }

    /**
     * Render order meta box
     */
    public function render_order_meta_box( $post_or_order_object ) {
        // Compatible with HPOS and legacy
        $order = ( $post_or_order_object instanceof \WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;

        if ( ! $order || strpos( $order->get_payment_method(), 'megasoft' ) === false ) {
            echo '<p>' . esc_html__( 'Esta orden no fue procesada con Mega Soft.', 'woocommerce-megasoft-gateway-v2' ) . '</p>';
            return;
        }

        $order_id = $order->get_id();
        $control = $order->get_meta( '_megasoft_v2_control' );
        $authorization = $order->get_meta( '_megasoft_v2_authorization' );
        $card_last_four = $order->get_meta( '_megasoft_v2_card_last_four' );
        $card_type = $order->get_meta( '_megasoft_v2_card_type' );
        $voucher_html = $order->get_meta( '_megasoft_voucher_html' );

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

            <?php if ( $voucher_html ) : ?>
                <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                    <strong><?php esc_html_e( 'Voucher:', 'woocommerce-megasoft-gateway-v2' ); ?></strong><br>
                    <?php
                    $download_url = add_query_arg( array(
                        'action'   => 'megasoft_download_voucher',
                        'order_id' => $order_id,
                        'nonce'    => wp_create_nonce( 'megasoft_voucher_' . $order_id ),
                    ), admin_url( 'admin.php' ) );
                    ?>
                    <a href="<?php echo esc_url( $download_url ); ?>" class="button button-secondary" style="margin-top: 5px;">
                        <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>
                        <?php esc_html_e( 'Descargar Voucher', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </a>
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
                $new_columns['megasoft_actions'] = __( 'Voucher', 'woocommerce-megasoft-gateway-v2' );
            }
        }

        return $new_columns;
    }

    /**
     * Render custom order column
     */
    public function render_order_column( $column, $post_id ) {
        $order = wc_get_order( $post_id );

        if ( ! $order || strpos( $order->get_payment_method(), 'megasoft' ) === false ) {
            return;
        }

        if ( $column === 'megasoft_control' ) {
            $control = $order->get_meta( '_megasoft_v2_control' );
            if ( $control ) {
                echo '<code>' . esc_html( $control ) . '</code>';
            } else {
                echo '—';
            }
        }

        if ( $column === 'megasoft_actions' ) {
            $voucher_html = $order->get_meta( '_megasoft_voucher_html' );

            if ( $voucher_html ) {
                $download_url = add_query_arg( array(
                    'action'   => 'megasoft_download_voucher',
                    'order_id' => $post_id,
                    'nonce'    => wp_create_nonce( 'megasoft_voucher_' . $post_id ),
                ), admin_url( 'admin.php' ) );

                echo '<a href="' . esc_url( $download_url ) . '" class="button button-small" title="' . esc_attr__( 'Descargar Voucher', 'woocommerce-megasoft-gateway-v2' ) . '">';
                echo '<span class="dashicons dashicons-download" style="line-height: 1.4;"></span>';
                echo '</a>';
            } else {
                echo '<span style="color: #999;">—</span>';
            }
        }
    }

    /**
     * Handle voucher download request
     */
    public function handle_voucher_download() {
        if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'megasoft_download_voucher' ) {
            return;
        }

        if ( ! isset( $_GET['order_id'] ) || ! isset( $_GET['nonce'] ) ) {
            wp_die( __( 'Parámetros inválidos', 'woocommerce-megasoft-gateway-v2' ) );
        }

        $order_id = intval( $_GET['order_id'] );
        $nonce = sanitize_text_field( $_GET['nonce'] );

        // Verify nonce
        if ( ! wp_verify_nonce( $nonce, 'megasoft_voucher_' . $order_id ) ) {
            wp_die( __( 'Verificación de seguridad fallida', 'woocommerce-megasoft-gateway-v2' ) );
        }

        // Verify user can manage orders
        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_die( __( 'No tienes permisos para realizar esta acción', 'woocommerce-megasoft-gateway-v2' ) );
        }

        // Get order
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_die( __( 'Orden no encontrada', 'woocommerce-megasoft-gateway-v2' ) );
        }

        // Get voucher HTML
        $voucher_html = $order->get_meta( '_megasoft_voucher_html' );

        if ( ! $voucher_html ) {
            wp_die( __( 'No se encontró el voucher para esta orden', 'woocommerce-megasoft-gateway-v2' ) );
        }

        $control = $order->get_meta( '_megasoft_v2_control' );

        // Create filename
        $filename = 'voucher-megasoft-orden-' . $order_id . '-control-' . $control . '.html';

        // Generate complete HTML document
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Voucher - Orden #' . $order_id . '</title>
    <style>
        body {
            font-family: "Courier New", monospace;
            margin: 20px;
            background: #f5f5f5;
        }
        .voucher-container {
            background: white;
            padding: 20px;
            max-width: 400px;
            margin: 0 auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        @media print {
            body {
                background: white;
                margin: 0;
            }
            .voucher-container {
                box-shadow: none;
                max-width: none;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="voucher-container">
        ' . $voucher_html . '
    </div>
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()">Imprimir</button>
        <button onclick="window.close()">Cerrar</button>
    </div>
</body>
</html>';

        // Send headers
        header( 'Content-Type: text/html; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . strlen( $html ) );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        echo $html;
        exit;
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
        // Use new simplified transaction saver
        $stats = MegaSoft_V2_Transaction_Saver::get_stats();

        return array(
            'total_transactions'    => $stats['total'],
            'approved_transactions' => $stats['approved'],
            'failed_transactions'   => $stats['failed'],
            'total_amount'          => $stats['total_amount'],
        );
    }

    /**
     * Get recent transactions
     */
    private function get_recent_transactions( $limit = 10 ) {
        // Use new simplified transaction saver
        return MegaSoft_V2_Transaction_Saver::get_recent( $limit );
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
            'approved' => '<span class="status-badge status-approved">Aprobada</span>',
            'failed'   => '<span class="status-badge status-failed">Rechazada</span>',
            'pending'  => '<span class="status-badge status-pending">Pendiente</span>',
            'declined' => '<span class="status-badge status-failed">Declinada</span>',
        );

        return $badges[ $status ] ?? '<span class="status-badge">' . esc_html( ucfirst( $status ) ) . '</span>';
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

    /**
     * AJAX handler to get transaction details
     */
    public function ajax_get_transaction_details() {
        check_ajax_referer( 'megasoft_v2_admin', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $transaction_id = isset( $_POST['transaction_id'] ) ? intval( $_POST['transaction_id'] ) : 0;

        if ( ! $transaction_id ) {
            wp_send_json_error( 'Invalid transaction ID' );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'megasoft_v2_transactions';

        $transaction = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $transaction_id
        ) );

        if ( ! $transaction ) {
            wp_send_json_error( 'Transaction not found' );
        }

        // Get order details
        $order = wc_get_order( $transaction->order_id );
        $order_data = array();

        if ( $order ) {
            $order_data = array(
                'order_id'     => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'order_status' => $order->get_status(),
                'customer'     => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'email'        => $order->get_billing_email(),
                'total'        => $order->get_total(),
                'currency'     => $order->get_currency(),
                'date'         => $order->get_date_created()->date( 'd/m/Y H:i' ),
                'edit_url'     => $order->get_edit_order_url(),
            );
        }

        // Format transaction data
        $response = array(
            'transaction' => array(
                'id'                 => $transaction->id,
                'control_number'     => $transaction->control_number,
                'authorization_code' => $transaction->authorization_code,
                'transaction_type'   => $transaction->transaction_type,
                'amount'             => wc_price( $transaction->amount ),
                'currency'           => $transaction->currency,
                'card_type'          => $transaction->card_type ? ucfirst( $transaction->card_type ) : '-',
                'card_last_four'     => $transaction->card_last_four ? '****' . $transaction->card_last_four : '-',
                'status'             => ucfirst( $transaction->status ),
                'status_class'       => $transaction->status,
                'raw_request'        => $transaction->raw_request,
                'raw_response'       => $transaction->raw_response,
                'error_message'      => $transaction->error_message,
                'created_at'         => mysql2date( 'd/m/Y H:i:s', $transaction->created_at ),
            ),
            'order' => $order_data,
        );

        wp_send_json_success( $response );
    }
}
