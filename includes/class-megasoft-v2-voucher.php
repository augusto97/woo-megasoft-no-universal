<?php
/**
 * MegaSoft Gateway v2 - Voucher Generator
 *
 * Generates printable vouchers for transactions according to
 * banking certification standards
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MegaSoft_V2_Voucher {

    /**
     * Generate voucher HTML from transaction data
     *
     * @param array $transaction_data Transaction data
     * @param array $response_data API response data
     * @return string Voucher HTML
     */
    public static function generate( $transaction_data, $response_data ) {
        $voucher_lines = array();

        // Parse voucher from API response if available
        if ( isset( $response_data['voucher'] ) && ! empty( $response_data['voucher'] ) ) {
            $voucher_lines = self::parse_api_voucher( $response_data['voucher'] );
        }

        // If no voucher from API, generate default voucher
        if ( empty( $voucher_lines ) ) {
            $voucher_lines = self::generate_default_voucher( $transaction_data, $response_data );
        }

        return self::render_voucher_html( $voucher_lines, $transaction_data, $response_data );
    }

    /**
     * Parse voucher from API response
     *
     * @param mixed $voucher_data Voucher data from API
     * @return array Array of voucher lines
     */
    private static function parse_api_voucher( $voucher_data ) {
        $lines = array();

        // If voucher is array with 'linea' elements
        if ( is_array( $voucher_data ) ) {
            if ( isset( $voucher_data['linea'] ) ) {
                // Single linea or array of lineas
                $lineas = is_array( $voucher_data['linea'] ) ? $voucher_data['linea'] : array( $voucher_data['linea'] );

                foreach ( $lineas as $linea ) {
                    if ( is_string( $linea ) ) {
                        $lines[] = $linea;
                    }
                }
            }
        } elseif ( is_string( $voucher_data ) ) {
            // If voucher is a string, split by newlines
            $lines = explode( "\n", $voucher_data );
        }

        return array_filter( $lines );
    }

    /**
     * Generate default voucher when API doesn't provide one
     *
     * @param array $transaction_data Transaction data
     * @param array $response_data API response data
     * @return array Array of voucher lines
     */
    private static function generate_default_voucher( $transaction_data, $response_data ) {
        $lines = array();

        $is_approved = isset( $response_data['codigo'] ) && $response_data['codigo'] === '00';
        $status_text = $is_approved ? 'APROBADA' : 'RECHAZADA';

        // Header
        $lines[] = '_';
        $lines[] = '<UT>__DUPLICADO</UT>';
        $lines[] = 'MEGA SOFT - PAYMENT GATEWAY';
        $lines[] = isset( $response_data['authname'] ) ? $response_data['authname'] : 'TRANSACCIÓN';
        $lines[] = strtoupper( $transaction_data['type'] ?? 'VENTA' );
        $lines[] = '_';

        // Merchant info
        $lines[] = get_bloginfo( 'name' );
        if ( isset( $transaction_data['merchant_info']['address'] ) ) {
            $lines[] = $transaction_data['merchant_info']['address'];
        }

        // RIF and affiliation
        if ( isset( $response_data['afiliacion'] ) ) {
            $lines[] = 'AFIL:' . $response_data['afiliacion'];
        }

        // Terminal info
        if ( isset( $response_data['terminal'] ) ) {
            $lines[] = 'TER:' . $response_data['terminal'];
        }
        if ( isset( $response_data['lote'] ) ) {
            $lines[] = 'LOTE:' . $response_data['lote'];
        }

        $lines[] = '_';

        // Date and time
        $lines[] = 'FECHA:' . date( 'd/m/Y H:i:s' );

        // Card or payment method info
        if ( isset( $response_data['tarjeta'] ) ) {
            $lines[] = 'TARJETA:' . $response_data['tarjeta'];
        } elseif ( isset( $transaction_data['card_last_four'] ) ) {
            $lines[] = 'TARJETA:' . str_repeat( '*', 12 ) . $transaction_data['card_last_four'];
        }

        // Reference and approval
        if ( isset( $response_data['referencia'] ) ) {
            $lines[] = 'REFERENCIA:' . $response_data['referencia'];
        }
        if ( isset( $response_data['authid'] ) && ! empty( $response_data['authid'] ) ) {
            $lines[] = 'APROBACION:' . $response_data['authid'];
        }

        // Sequence and control
        if ( isset( $response_data['seqnum'] ) ) {
            $lines[] = 'SECUENCIA:' . $response_data['seqnum'];
        }
        if ( isset( $response_data['control'] ) ) {
            $lines[] = 'CONTROL:' . $response_data['control'];
        }

        $lines[] = '_';

        // Amount
        if ( isset( $transaction_data['amount'] ) ) {
            $currency = $transaction_data['currency'] ?? 'VES';
            $amount_formatted = number_format( $transaction_data['amount'], 2, ',', '.' );
            $lines[] = 'MONTO ' . $currency . ': ' . $amount_formatted;
        }

        // Status
        $lines[] = '_';
        $lines[] = 'ESTADO: ' . $status_text;

        if ( ! $is_approved && isset( $response_data['descripcion'] ) ) {
            $lines[] = 'MOTIVO: ' . strtoupper( $response_data['descripcion'] );
        }

        $lines[] = '_';
        $lines[] = '_';

        return $lines;
    }

    /**
     * Render voucher HTML
     *
     * @param array $lines Voucher lines
     * @param array $transaction_data Transaction data
     * @param array $response_data Response data
     * @return string HTML
     */
    private static function render_voucher_html( $lines, $transaction_data, $response_data ) {
        $is_approved = isset( $response_data['codigo'] ) && $response_data['codigo'] === '00';
        $status_class = $is_approved ? 'approved' : 'rejected';
        $status_text = $is_approved ? __( 'APROBADA', 'woocommerce-megasoft-gateway-v2' ) : __( 'RECHAZADA', 'woocommerce-megasoft-gateway-v2' );

        ob_start();
        ?>
        <div class="megasoft-voucher-container">
            <div class="megasoft-voucher <?php echo esc_attr( $status_class ); ?>">
                <div class="voucher-header">
                    <h2><?php echo esc_html( $status_text ); ?></h2>
                    <?php if ( ! $is_approved && isset( $response_data['descripcion'] ) ) : ?>
                        <p class="error-message"><?php echo esc_html( $response_data['descripcion'] ); ?></p>
                    <?php endif; ?>
                </div>

                <div class="voucher-content">
                    <?php foreach ( $lines as $line ) : ?>
                        <?php
                        // Check for special formatting tags
                        $line = str_replace( '<UT>', '<span class="duplicate-mark">', $line );
                        $line = str_replace( '</UT>', '</span>', $line );

                        // Check if line is separator
                        if ( trim( $line ) === '_' ) {
                            echo '<div class="voucher-separator"></div>';
                        } else {
                            echo '<div class="voucher-line">' . wp_kses( $line, array( 'span' => array( 'class' => array() ) ) ) . '</div>';
                        }
                        ?>
                    <?php endforeach; ?>
                </div>

                <div class="voucher-footer">
                    <p class="print-notice"><?php esc_html_e( 'Este comprobante es válido sin necesidad de firma o sello', 'woocommerce-megasoft-gateway-v2' ); ?></p>
                    <p class="timestamp"><?php echo esc_html( sprintf( __( 'Generado: %s', 'woocommerce-megasoft-gateway-v2' ), date( 'd/m/Y H:i:s' ) ) ); ?></p>
                </div>

                <button type="button" class="megasoft-print-voucher" onclick="window.print();">
                    <?php esc_html_e( 'Imprimir Comprobante', 'woocommerce-megasoft-gateway-v2' ); ?>
                </button>
            </div>

            <style>
                .megasoft-voucher-container {
                    max-width: 400px;
                    margin: 20px auto;
                    font-family: 'Courier New', monospace;
                }

                .megasoft-voucher {
                    border: 2px solid #ddd;
                    padding: 20px;
                    background: #fff;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }

                .megasoft-voucher.approved {
                    border-color: #4caf50;
                }

                .megasoft-voucher.rejected {
                    border-color: #f44336;
                }

                .voucher-header {
                    text-align: center;
                    margin-bottom: 20px;
                    padding-bottom: 15px;
                    border-bottom: 2px dashed #333;
                }

                .voucher-header h2 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: bold;
                }

                .megasoft-voucher.approved .voucher-header h2 {
                    color: #4caf50;
                }

                .megasoft-voucher.rejected .voucher-header h2 {
                    color: #f44336;
                }

                .error-message {
                    margin: 10px 0 0;
                    color: #f44336;
                    font-size: 14px;
                }

                .voucher-content {
                    font-size: 13px;
                    line-height: 1.4;
                }

                .voucher-line {
                    margin: 2px 0;
                    white-space: pre-wrap;
                    word-break: break-word;
                }

                .voucher-separator {
                    height: 10px;
                }

                .duplicate-mark {
                    font-weight: bold;
                    text-decoration: underline;
                }

                .voucher-footer {
                    margin-top: 20px;
                    padding-top: 15px;
                    border-top: 2px dashed #333;
                    text-align: center;
                    font-size: 11px;
                    color: #666;
                }

                .print-notice {
                    margin: 5px 0;
                }

                .timestamp {
                    margin: 5px 0;
                }

                .megasoft-print-voucher {
                    display: block;
                    width: 100%;
                    margin-top: 15px;
                    padding: 12px;
                    background: #2196F3;
                    color: #fff;
                    border: none;
                    border-radius: 4px;
                    font-size: 14px;
                    font-weight: bold;
                    cursor: pointer;
                    transition: background 0.3s;
                }

                .megasoft-print-voucher:hover {
                    background: #1976D2;
                }

                /* Print styles */
                @media print {
                    body * {
                        visibility: hidden;
                    }

                    .megasoft-voucher-container,
                    .megasoft-voucher-container * {
                        visibility: visible;
                    }

                    .megasoft-voucher-container {
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 100%;
                    }

                    .megasoft-print-voucher {
                        display: none;
                    }

                    .megasoft-voucher {
                        border: none;
                        box-shadow: none;
                        padding: 0;
                    }
                }
            </style>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Save voucher HTML to order meta
     *
     * @param int $order_id Order ID
     * @param string $voucher_html Voucher HTML
     * @return bool Success
     */
    public static function save_to_order( $order_id, $voucher_html ) {
        return update_post_meta( $order_id, '_megasoft_voucher_html', $voucher_html );
    }

    /**
     * Get voucher HTML from order meta
     *
     * @param int $order_id Order ID
     * @return string|false Voucher HTML or false if not found
     */
    public static function get_from_order( $order_id ) {
        return get_post_meta( $order_id, '_megasoft_voucher_html', true );
    }

    /**
     * Display voucher in order details page
     *
     * @param WC_Order $order Order object
     */
    public static function display_in_order_details( $order ) {
        $voucher_html = self::get_from_order( $order->get_id() );

        if ( $voucher_html ) {
            echo '<section class="woocommerce-order-megasoft-voucher">';
            echo '<h2>' . esc_html__( 'Comprobante de Pago', 'woocommerce-megasoft-gateway-v2' ) . '</h2>';
            echo $voucher_html; // Already escaped in render_voucher_html
            echo '</section>';
        }
    }

    /**
     * Add voucher download link to order actions
     *
     * @param array $actions Order actions
     * @param WC_Order $order Order object
     * @return array Modified actions
     */
    public static function add_voucher_action( $actions, $order ) {
        $voucher_html = self::get_from_order( $order->get_id() );

        if ( $voucher_html ) {
            $actions['view_voucher'] = array(
                'url'  => add_query_arg( array(
                    'action' => 'megasoft_view_voucher',
                    'order_id' => $order->get_id(),
                    'nonce' => wp_create_nonce( 'megasoft_voucher_' . $order->get_id() ),
                ), admin_url( 'admin-ajax.php' ) ),
                'name' => __( 'Ver Comprobante', 'woocommerce-megasoft-gateway-v2' ),
            );
        }

        return $actions;
    }

    /**
     * Handle voucher view AJAX request
     */
    public static function handle_view_voucher_ajax() {
        if ( ! isset( $_GET['order_id'] ) || ! isset( $_GET['nonce'] ) ) {
            wp_die( __( 'Parámetros inválidos', 'woocommerce-megasoft-gateway-v2' ) );
        }

        $order_id = intval( $_GET['order_id'] );

        if ( ! wp_verify_nonce( $_GET['nonce'], 'megasoft_voucher_' . $order_id ) ) {
            wp_die( __( 'Nonce inválido', 'woocommerce-megasoft-gateway-v2' ) );
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_die( __( 'Orden no encontrada', 'woocommerce-megasoft-gateway-v2' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'view_order', $order_id ) && get_current_user_id() !== $order->get_customer_id() ) {
            wp_die( __( 'No tienes permiso para ver este comprobante', 'woocommerce-megasoft-gateway-v2' ) );
        }

        $voucher_html = self::get_from_order( $order_id );

        if ( ! $voucher_html ) {
            wp_die( __( 'Comprobante no encontrado', 'woocommerce-megasoft-gateway-v2' ) );
        }

        // Output voucher
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html( sprintf( __( 'Comprobante - Orden #%s', 'woocommerce-megasoft-gateway-v2' ), $order->get_order_number() ) ); ?></title>
            <?php wp_head(); ?>
        </head>
        <body>
            <div class="megasoft-voucher-page">
                <h1><?php echo esc_html( sprintf( __( 'Comprobante - Orden #%s', 'woocommerce-megasoft-gateway-v2' ), $order->get_order_number() ) ); ?></h1>
                <?php echo $voucher_html; // Already escaped ?>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }
}
