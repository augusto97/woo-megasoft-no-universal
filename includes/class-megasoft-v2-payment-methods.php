<?php
/**
 * MegaSoft Gateway v2 - Additional Payment Methods
 *
 * Handles alternative payment methods:
 * - Pago Móvil C2P (Cliente a Persona)
 * - Pago Móvil P2C (Persona a Cliente)
 * - Criptomonedas
 * - Banplus Pay
 * - Zelle
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pago Móvil C2P Gateway
 */
class WC_Gateway_MegaSoft_Pago_Movil_C2P extends WC_Payment_Gateway {

    private $api;
    private $logger;

    public function __construct() {
        $this->id                 = 'megasoft_pago_movil_c2p';
        $this->icon               = '';
        $this->has_fields         = true;
        $this->method_title       = __( 'Pago Móvil C2P (Mega Soft)', 'woocommerce-megasoft-gateway-v2' );
        $this->method_description = __( 'Pago Móvil Cliente a Persona (C2P) - El cliente envía el pago desde su banco.', 'woocommerce-megasoft-gateway-v2' );

        $this->supports = array( 'products' );

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled      = $this->get_option( 'enabled' );
        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );
        $this->testmode     = 'yes' === $this->get_option( 'testmode' );

        // Get credentials from main gateway
        $main_gateway_settings = get_option( 'woocommerce_megasoft_v2_settings', array() );

        $this->api = new MegaSoft_V2_API(
            $main_gateway_settings['cod_afiliacion'] ?? '',
            $main_gateway_settings['api_user'] ?? '',
            $main_gateway_settings['api_password'] ?? '',
            $this->testmode
        );

        $this->logger = new MegaSoft_V2_Logger( true, 'info' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Habilitar/Deshabilitar', 'woocommerce-megasoft-gateway-v2' ),
                'type'    => 'checkbox',
                'label'   => __( 'Habilitar Pago Móvil C2P', 'woocommerce-megasoft-gateway-v2' ),
                'default' => 'no',
            ),
            'title' => array(
                'title'       => __( 'Título', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'text',
                'default'     => __( 'Pago Móvil C2P', 'woocommerce-megasoft-gateway-v2' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Descripción', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'textarea',
                'default'     => __( 'Paga desde tu banca móvil de forma segura.', 'woocommerce-megasoft-gateway-v2' ),
                'desc_tip'    => true,
            ),
            'testmode' => array(
                'title'   => __( 'Modo de Prueba', 'woocommerce-megasoft-gateway-v2' ),
                'type'    => 'checkbox',
                'label'   => __( 'Habilitar modo de prueba', 'woocommerce-megasoft-gateway-v2' ),
                'default' => 'yes',
            ),
            'receiver_phone' => array(
                'title'       => __( 'Teléfono Receptor', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'text',
                'description' => __( 'Número de teléfono que recibirá el Pago Móvil (formato: 04141234567)', 'woocommerce-megasoft-gateway-v2' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'receiver_bank' => array(
                'title'   => __( 'Banco Receptor', 'woocommerce-megasoft-gateway-v2' ),
                'type'    => 'select',
                'options' => $this->get_venezuelan_banks(),
                'default' => '0102',
            ),
        );
    }

    public function payment_fields() {
        if ( $this->description ) {
            echo wpautop( wp_kses_post( $this->description ) );
        }
        ?>
        <fieldset class="megasoft-v2-pago-movil-form">
            <p class="form-row form-row-wide">
                <label for="pm_phone"><?php esc_html_e( 'Teléfono de Origen', 'woocommerce-megasoft-gateway-v2' ); ?> <span class="required">*</span></label>
                <input type="text" id="pm_phone" name="pm_phone" placeholder="04141234567" maxlength="11" pattern="[0-9]{11}" required />
            </p>

            <p class="form-row form-row-wide">
                <label for="pm_bank"><?php esc_html_e( 'Banco de Origen', 'woocommerce-megasoft-gateway-v2' ); ?> <span class="required">*</span></label>
                <select id="pm_bank" name="pm_bank" required>
                    <?php foreach ( $this->get_venezuelan_banks() as $code => $name ) : ?>
                        <option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p class="form-row form-row-first">
                <label for="pm_doc_type"><?php esc_html_e( 'Tipo de Documento', 'woocommerce-megasoft-gateway-v2' ); ?> <span class="required">*</span></label>
                <select id="pm_doc_type" name="pm_doc_type" required>
                    <option value="V">V - Venezolano</option>
                    <option value="E">E - Extranjero</option>
                    <option value="J">J - Jurídico</option>
                    <option value="G">G - Gubernamental</option>
                    <option value="P">P - Pasaporte</option>
                </select>
            </p>

            <p class="form-row form-row-last">
                <label for="pm_doc_number"><?php esc_html_e( 'Número de Documento', 'woocommerce-megasoft-gateway-v2' ); ?> <span class="required">*</span></label>
                <input type="text" id="pm_doc_number" name="pm_doc_number" placeholder="12345678" maxlength="20" required />
            </p>

            <p class="form-row form-row-wide">
                <label for="pm_codigo_c2p"><?php esc_html_e( 'Código C2P', 'woocommerce-megasoft-gateway-v2' ); ?> <span class="required">*</span></label>
                <input type="text" id="pm_codigo_c2p" name="pm_codigo_c2p" placeholder="12345678" maxlength="12" pattern="[0-9]{1,12}" required />
                <small><?php esc_html_e( 'Código de confirmación del Pago Móvil C2P', 'woocommerce-megasoft-gateway-v2' ); ?></small>
            </p>
        </fieldset>
        <?php
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        try {
            // Get form data
            $phone      = sanitize_text_field( $_POST['pm_phone'] ?? '' );
            $bank       = sanitize_text_field( $_POST['pm_bank'] ?? '' );
            $doc_type   = sanitize_text_field( $_POST['pm_doc_type'] ?? 'V' );
            $doc_number = sanitize_text_field( $_POST['pm_doc_number'] ?? '' );
            $codigo_c2p = sanitize_text_field( $_POST['pm_codigo_c2p'] ?? '' );

            // Validate
            if ( empty( $phone ) || empty( $bank ) || empty( $doc_number ) || empty( $codigo_c2p ) ) {
                throw new Exception( __( 'Todos los campos son requeridos', 'woocommerce-megasoft-gateway-v2' ) );
            }

            // Pre-registro
            $preregistro = $this->api->preregistro();

            if ( is_wp_error( $preregistro ) ) {
                throw new Exception( $preregistro->get_error_message() );
            }

            if ( ! $preregistro['success'] ) {
                throw new Exception( $preregistro['message'] ?? __( 'Error en pre-registro', 'woocommerce-megasoft-gateway-v2' ) );
            }

            $control = $preregistro['control'];
            $order->update_meta_data( '_megasoft_v2_control', $control );
            $order->save();

            // Procesar Pago Móvil C2P
            $payment_data = array(
                'control'      => $control,
                'cid'          => $doc_type . $doc_number,
                'telefono'     => $phone,
                'codigobanco'  => $bank,
                'codigoc2p'    => $codigo_c2p,
                'amount'       => $order->get_total(),
                'factura'      => $order->get_order_number(),
            );

            $response = $this->api->procesar_pago_movil_c2p( $payment_data );

            if ( is_wp_error( $response ) ) {
                throw new Exception( $response->get_error_message() );
            }

            // NOTE: Do NOT throw exception if response['success'] is false
            // success=false just means payment was rejected (blocked, insufficient funds, etc.)
            // We need to continue to query_status to get voucher data and show it

            // Query status
            $status = $this->api->query_status( $control, 'C2P' );

            if ( is_wp_error( $status ) ) {
                throw new Exception( $status->get_error_message() );
            }

            // Prepare transaction data for voucher
            $transaction_data = array(
                'type'          => 'Pago Móvil C2P',
                'amount'        => $order->get_total(),
                'currency'      => $order->get_currency(),
                'phone'         => $phone,
                'bank'          => $bank,
                'codigo_c2p'    => $codigo_c2p,
                'merchant_info' => array(
                    'name'    => get_bloginfo( 'name' ),
                    'address' => get_option( 'woocommerce_store_address' ),
                ),
            );

            // Generate and save voucher regardless of status
            $this->generate_and_save_voucher( $order, $transaction_data, $status );

            if ( $status['success'] && $status['codigo'] === '00' ) {
                // Payment approved
                $order->payment_complete( $control );
                $order->add_order_note( sprintf( __( 'Pago Móvil C2P procesado. Control: %s', 'woocommerce-megasoft-gateway-v2' ), $control ) );

                // Clear cart only on success
                WC()->cart->empty_cart();
            } else {
                // Payment rejected - mark as failed but still redirect to show voucher
                $error_message = $status['mensaje'] ?? __( 'Pago rechazado', 'woocommerce-megasoft-gateway-v2' );
                $order->update_status( 'failed', sprintf( __( 'Pago rechazado. Código: %s - %s', 'woocommerce-megasoft-gateway-v2' ), $status['codigo'], $error_message ) );

                // Add error notice that will be displayed on thank you page
                wc_add_notice( $error_message, 'error' );
            }

            // Always redirect to thank you page to show voucher
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            );

        } catch ( Exception $e ) {
            // Only catch exceptions from API errors, not payment rejection
            wc_add_notice( $e->getMessage(), 'error' );
            return array(
                'result'   => 'failure',
                'redirect' => '',
            );
        }
    }

    /**
     * Generate and save voucher for transaction
     */
    private function generate_and_save_voucher( $order, $transaction_data, $response ) {
        try {
            $voucher_html = MegaSoft_V2_Voucher::generate( $transaction_data, $response );
            MegaSoft_V2_Voucher::save_to_order( $order->get_id(), $voucher_html );

            $this->logger->debug( 'Voucher C2P generado y guardado', array(
                'order_id' => $order->get_id(),
            ) );
        } catch ( Exception $e ) {
            $this->logger->error( 'Error al generar voucher C2P', array(
                'order_id' => $order->get_id(),
                'error'    => $e->getMessage(),
            ) );
        }
    }

    /**
     * Display thank you page with voucher
     */
    public function thankyou_page( $order_id ) {
        if ( ! $order_id ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Display voucher using the voucher class
        MegaSoft_V2_Voucher::display_in_order_details( $order );
    }

    private function get_venezuelan_banks() {
        return array(
            '0102' => 'Banco de Venezuela',
            '0104' => 'Banco Venezolano de Crédito',
            '0105' => 'Banco Mercantil',
            '0108' => 'Banco Provincial',
            '0114' => 'Banco del Caribe',
            '0115' => 'Banco Exterior',
            '0128' => 'Banco Caroní',
            '0134' => 'Banesco',
            '0137' => 'Banco Sofitasa',
            '0138' => 'Banco Plaza',
            '0146' => 'Bangente',
            '0151' => 'Banco Fondo Común (BFC)',
            '0156' => '100% Banco',
            '0157' => 'Banco del Sur',
            '0163' => 'Banco del Tesoro',
            '0166' => 'Banco Agrícola de Venezuela',
            '0168' => 'Bancrecer',
            '0169' => 'Banco Activo',
            '0171' => 'Banco Activo',
            '0172' => 'Bancamiga',
            '0173' => 'Banco Internacional de Desarrollo',
            '0174' => 'Banplus',
            '0175' => 'Banco Bicentenario',
            '0177' => 'Banco de la Fuerza Armada Nacional Bolivariana',
            '0191' => 'Banco Nacional de Crédito (BNC)',
        );
    }
}

/**
 * Pago Móvil P2C Gateway
 */
class WC_Gateway_MegaSoft_Pago_Movil_P2C extends WC_Payment_Gateway {

    private $api;
    private $logger;

    public function __construct() {
        $this->id                 = 'megasoft_pago_movil_p2c';
        $this->icon               = '';
        $this->has_fields         = true;
        $this->method_title       = __( 'Pago Móvil P2C (Mega Soft)', 'woocommerce-megasoft-gateway-v2' );
        $this->method_description = __( 'Pago Móvil Persona a Cliente (P2C) - Tú envías el pago al cliente.', 'woocommerce-megasoft-gateway-v2' );

        $this->supports = array( 'products' );

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled      = $this->get_option( 'enabled' );
        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );
        $this->testmode     = 'yes' === $this->get_option( 'testmode' );

        // Get credentials from main gateway
        $main_gateway_settings = get_option( 'woocommerce_megasoft_v2_settings', array() );

        $this->api = new MegaSoft_V2_API(
            $main_gateway_settings['cod_afiliacion'] ?? '',
            $main_gateway_settings['api_user'] ?? '',
            $main_gateway_settings['api_password'] ?? '',
            $this->testmode
        );

        $this->logger = new MegaSoft_V2_Logger( true, 'info' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Habilitar/Deshabilitar', 'woocommerce-megasoft-gateway-v2' ),
                'type'    => 'checkbox',
                'label'   => __( 'Habilitar Pago Móvil P2C', 'woocommerce-megasoft-gateway-v2' ),
                'default' => 'no',
            ),
            'title' => array(
                'title'       => __( 'Título', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'text',
                'default'     => __( 'Pago Móvil P2C', 'woocommerce-megasoft-gateway-v2' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Descripción', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'textarea',
                'default'     => __( 'Recibe el pago directo en tu cuenta.', 'woocommerce-megasoft-gateway-v2' ),
                'desc_tip'    => true,
            ),
            'testmode' => array(
                'title'   => __( 'Modo de Prueba', 'woocommerce-megasoft-gateway-v2' ),
                'type'    => 'checkbox',
                'label'   => __( 'Habilitar modo de prueba', 'woocommerce-megasoft-gateway-v2' ),
                'default' => 'yes',
            ),
            'merchant_phone' => array(
                'title'       => __( 'Teléfono del Comercio', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'text',
                'description' => __( 'Número de teléfono desde el cual se enviará el Pago Móvil (formato: 04141234567)', 'woocommerce-megasoft-gateway-v2' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'merchant_bank' => array(
                'title'   => __( 'Banco del Comercio', 'woocommerce-megasoft-gateway-v2' ),
                'type'    => 'select',
                'options' => $this->get_venezuelan_banks(),
                'default' => '0102',
            ),
            'payment_type' => array(
                'title'   => __( 'Tipo de Pago', 'woocommerce-megasoft-gateway-v2' ),
                'type'    => 'select',
                'options' => array(
                    '10' => 'Bolívares',
                    '40' => 'Dólares',
                    '90' => 'Euros',
                ),
                'default' => '10',
            ),
        );
    }

    public function payment_fields() {
        if ( $this->description ) {
            echo wpautop( wp_kses_post( $this->description ) );
        }
        ?>
        <fieldset class="megasoft-v2-pago-movil-p2c-form">
            <p><?php esc_html_e( 'El comercio te enviará un Pago Móvil. Por favor proporciona tu información:', 'woocommerce-megasoft-gateway-v2' ); ?></p>

            <p class="form-row form-row-wide">
                <label for="p2c_phone"><?php esc_html_e( 'Tu Teléfono', 'woocommerce-megasoft-gateway-v2' ); ?> <span class="required">*</span></label>
                <input type="text" id="p2c_phone" name="p2c_phone" placeholder="04141234567" maxlength="11" pattern="[0-9]{11}" required />
            </p>

            <p class="form-row form-row-wide">
                <label for="p2c_bank"><?php esc_html_e( 'Tu Banco', 'woocommerce-megasoft-gateway-v2' ); ?> <span class="required">*</span></label>
                <select id="p2c_bank" name="p2c_bank" required>
                    <?php foreach ( $this->get_venezuelan_banks() as $code => $name ) : ?>
                        <option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p class="form-row form-row-first">
                <label for="p2c_doc_type"><?php esc_html_e( 'Tipo de Documento', 'woocommerce-megasoft-gateway-v2' ); ?> <span class="required">*</span></label>
                <select id="p2c_doc_type" name="p2c_doc_type" required>
                    <option value="V">V - Venezolano</option>
                    <option value="E">E - Extranjero</option>
                    <option value="J">J - Jurídico</option>
                    <option value="G">G - Gubernamental</option>
                    <option value="P">P - Pasaporte</option>
                </select>
            </p>

            <p class="form-row form-row-last">
                <label for="p2c_doc_number"><?php esc_html_e( 'Número de Documento', 'woocommerce-megasoft-gateway-v2' ); ?> <span class="required">*</span></label>
                <input type="text" id="p2c_doc_number" name="p2c_doc_number" placeholder="12345678" maxlength="20" required />
            </p>
        </fieldset>
        <?php
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        try {
            $this->logger->info( 'P2C process_payment iniciado', array( 'order_id' => $order_id ) );

            // Get form data
            $customer_phone = sanitize_text_field( $_POST['p2c_phone'] ?? '' );
            $customer_bank  = sanitize_text_field( $_POST['p2c_bank'] ?? '' );
            $doc_type       = sanitize_text_field( $_POST['p2c_doc_type'] ?? 'V' );
            $doc_number     = sanitize_text_field( $_POST['p2c_doc_number'] ?? '' );

            $this->logger->info( 'P2C datos del formulario obtenidos', array(
                'customer_phone' => $customer_phone,
                'customer_bank' => $customer_bank,
                'doc_type' => $doc_type,
            ) );

            // Validate
            if ( empty( $customer_phone ) || empty( $customer_bank ) || empty( $doc_number ) ) {
                throw new Exception( __( 'Todos los campos son requeridos', 'woocommerce-megasoft-gateway-v2' ) );
            }

            // Get merchant settings
            $merchant_phone = $this->get_option( 'merchant_phone' );
            $merchant_bank  = $this->get_option( 'merchant_bank' );
            $payment_type   = $this->get_option( 'payment_type', '10' );

            $this->logger->info( 'P2C configuración del comercio', array(
                'merchant_phone' => $merchant_phone,
                'merchant_bank' => $merchant_bank,
                'payment_type' => $payment_type,
            ) );

            if ( empty( $merchant_phone ) || empty( $merchant_bank ) ) {
                throw new Exception( __( 'Configuración del comercio incompleta. Contacte al administrador.', 'woocommerce-megasoft-gateway-v2' ) );
            }

            // Pre-registro
            $this->logger->info( 'P2C iniciando preregistro' );
            $preregistro = $this->api->preregistro();

            if ( is_wp_error( $preregistro ) ) {
                throw new Exception( $preregistro->get_error_message() );
            }

            if ( ! $preregistro['success'] ) {
                throw new Exception( $preregistro['message'] ?? __( 'Error en pre-registro', 'woocommerce-megasoft-gateway-v2' ) );
            }

            $control = $preregistro['control'];
            $order->update_meta_data( '_megasoft_v2_control', $control );
            $order->update_meta_data( '_megasoft_v2_customer_phone', $customer_phone );
            $order->update_meta_data( '_megasoft_v2_customer_bank', $customer_bank );
            $order->update_meta_data( '_megasoft_v2_customer_cid', $doc_type . $doc_number );
            $order->save();

            // Procesar Pago Móvil P2C
            $payment_data = array(
                'control'            => $control,
                'telefonoCliente'    => $customer_phone,
                'codigobancoCliente' => $customer_bank,
                'telefonoComercio'   => $merchant_phone,
                'codigobancoComercio'=> $merchant_bank,
                'tipoPago'           => $payment_type,
                'amount'             => $order->get_total(),
                'factura'            => $order->get_order_number(),
            );

            $response = $this->api->procesar_pago_movil_p2c( $payment_data );

            if ( is_wp_error( $response ) ) {
                throw new Exception( $response->get_error_message() );
            }

            // NOTE: Do NOT throw exception if response['success'] is false
            // success=false just means payment was rejected
            // We need to continue to query_status to get voucher data and show it

            // Query status
            $status = $this->api->query_status( $control, 'P2C' );

            if ( is_wp_error( $status ) ) {
                throw new Exception( $status->get_error_message() );
            }

            // Prepare transaction data for voucher
            $transaction_data = array(
                'type'           => 'Pago Móvil P2C',
                'amount'         => $order->get_total(),
                'currency'       => $order->get_currency(),
                'customer_phone' => $customer_phone,
                'customer_bank'  => $customer_bank,
                'merchant_phone' => $merchant_phone,
                'merchant_bank'  => $merchant_bank,
                'merchant_info'  => array(
                    'name'    => get_bloginfo( 'name' ),
                    'address' => get_option( 'woocommerce_store_address' ),
                ),
            );

            // Generate and save voucher regardless of status
            $this->generate_and_save_voucher( $order, $transaction_data, $status );

            if ( $status['success'] && $status['codigo'] === '00' ) {
                // Payment approved
                $order->payment_complete( $control );
                $order->add_order_note( sprintf( __( 'Pago Móvil P2C procesado. Control: %s', 'woocommerce-megasoft-gateway-v2' ), $control ) );

                // Clear cart only on success
                WC()->cart->empty_cart();
            } else {
                // Payment rejected - mark as failed but still redirect to show voucher
                $error_message = $status['mensaje'] ?? __( 'Pago rechazado', 'woocommerce-megasoft-gateway-v2' );
                $order->update_status( 'failed', sprintf( __( 'Pago rechazado. Código: %s - %s', 'woocommerce-megasoft-gateway-v2' ), $status['codigo'], $error_message ) );

                // Add error notice that will be displayed on thank you page
                wc_add_notice( $error_message, 'error' );
            }

            // Always redirect to thank you page to show voucher
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            );

        } catch ( Exception $e ) {
            // Only catch exceptions from API errors, not payment rejection
            $this->logger->error( 'P2C Exception capturada', array(
                'order_id' => $order_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ) );
            wc_add_notice( $e->getMessage(), 'error' );
            return array(
                'result'   => 'failure',
                'redirect' => '',
            );
        } catch ( Error $e ) {
            // Catch PHP 7+ errors
            $this->logger->error( 'P2C Error fatal capturado', array(
                'order_id' => $order_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ) );
            wc_add_notice( __( 'Error interno del servidor. Por favor contacte al administrador.', 'woocommerce-megasoft-gateway-v2' ), 'error' );
            return array(
                'result'   => 'failure',
                'redirect' => '',
            );
        }
    }

    /**
     * Generate and save voucher for transaction
     */
    private function generate_and_save_voucher( $order, $transaction_data, $response ) {
        try {
            $voucher_html = MegaSoft_V2_Voucher::generate( $transaction_data, $response );
            MegaSoft_V2_Voucher::save_to_order( $order->get_id(), $voucher_html );

            $this->logger->debug( 'Voucher P2C generado y guardado', array(
                'order_id' => $order->get_id(),
            ) );
        } catch ( Exception $e ) {
            $this->logger->error( 'Error al generar voucher P2C', array(
                'order_id' => $order->get_id(),
                'error'    => $e->getMessage(),
            ) );
        }
    }

    /**
     * Display thank you page with voucher
     */
    public function thankyou_page( $order_id ) {
        if ( ! $order_id ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Display voucher using the voucher class
        MegaSoft_V2_Voucher::display_in_order_details( $order );
    }

    private function get_venezuelan_banks() {
        return array(
            '0102' => 'Banco de Venezuela',
            '0104' => 'Banco Venezolano de Crédito',
            '0105' => 'Banco Mercantil',
            '0108' => 'Banco Provincial',
            '0114' => 'Banco del Caribe',
            '0115' => 'Banco Exterior',
            '0128' => 'Banco Caroní',
            '0134' => 'Banesco',
            '0137' => 'Banco Sofitasa',
            '0138' => 'Banco Plaza',
            '0146' => 'Bangente',
            '0151' => 'Banco Fondo Común (BFC)',
            '0156' => '100% Banco',
            '0157' => 'Banco del Sur',
            '0163' => 'Banco del Tesoro',
            '0166' => 'Banco Agrícola de Venezuela',
            '0168' => 'Bancrecer',
            '0169' => 'Banco Activo',
            '0171' => 'Banco Activo',
            '0172' => 'Bancamiga',
            '0173' => 'Banco Internacional de Desarrollo',
            '0174' => 'Banplus',
            '0175' => 'Banco Bicentenario',
            '0177' => 'Banco de la Fuerza Armada Nacional Bolivariana',
            '0191' => 'Banco Nacional de Crédito (BNC)',
        );
    }
}

/**
 * Crédito Inmediato Gateway
 */
class WC_Gateway_MegaSoft_Credito_Inmediato extends WC_Payment_Gateway {

    private $api;
    private $logger;

    public function __construct() {
        $this->id                 = 'megasoft_credito_inmediato';
        $this->icon               = '';
        $this->has_fields         = true;
        $this->method_title       = __( 'Crédito Inmediato (Mega Soft)', 'woocommerce-megasoft-gateway-v2' );
        $this->method_description = __( 'Transferencia bancaria directa entre cuentas.', 'woocommerce-megasoft-gateway-v2' );

        $this->supports = array( 'products' );

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled      = $this->get_option( 'enabled' );
        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );
        $this->testmode     = 'yes' === $this->get_option( 'testmode' );

        // Get credentials from main gateway
        $main_gateway_settings = get_option( 'woocommerce_megasoft_v2_settings', array() );

        $this->api = new MegaSoft_V2_API(
            $main_gateway_settings['cod_afiliacion'] ?? '',
            $main_gateway_settings['api_user'] ?? '',
            $main_gateway_settings['api_password'] ?? '',
            $this->testmode
        );

        $this->logger = new MegaSoft_V2_Logger( true, 'info' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Habilitar/Deshabilitar', 'woocommerce-megasoft-gateway-v2' ),
                'type'    => 'checkbox',
                'label'   => __( 'Habilitar Crédito Inmediato', 'woocommerce-megasoft-gateway-v2' ),
                'default' => 'no',
            ),
            'title' => array(
                'title'       => __( 'Título', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'text',
                'default'     => __( 'Crédito Inmediato', 'woocommerce-megasoft-gateway-v2' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Descripción', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'textarea',
                'default'     => __( 'Transferencia directa entre cuentas bancarias.', 'woocommerce-megasoft-gateway-v2' ),
                'desc_tip'    => true,
            ),
            'testmode' => array(
                'title'   => __( 'Modo de Prueba', 'woocommerce-megasoft-gateway-v2' ),
                'type'    => 'checkbox',
                'label'   => __( 'Habilitar modo de prueba', 'woocommerce-megasoft-gateway-v2' ),
                'default' => 'yes',
            ),
            'destination_account' => array(
                'title'       => __( 'Cuenta Destino', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'text',
                'description' => __( 'Número de cuenta que recibirá la transferencia (20 dígitos)', 'woocommerce-megasoft-gateway-v2' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
        );
    }

    public function payment_fields() {
        if ( $this->description ) {
            echo wpautop( wp_kses_post( $this->description ) );
        }
        ?>
        <fieldset class="megasoft-v2-credito-inmediato-form">
            <p class="form-row form-row-first">
                <label for="ci_doc_type"><?php esc_html_e( 'Tipo de Documento', 'woocommerce-megasoft-gateway-v2' ); ?> <span class="required">*</span></label>
                <select id="ci_doc_type" name="ci_doc_type" required>
                    <option value="V">V - Venezolano</option>
                    <option value="E">E - Extranjero</option>
                    <option value="J">J - Jurídico</option>
                    <option value="G">G - Gubernamental</option>
                    <option value="P">P - Pasaporte</option>
                </select>
            </p>

            <p class="form-row form-row-last">
                <label for="ci_doc_number"><?php esc_html_e( 'Número de Documento', 'woocommerce-megasoft-gateway-v2' ); ?> <span class="required">*</span></label>
                <input type="text" id="ci_doc_number" name="ci_doc_number" placeholder="12345678" maxlength="20" required />
            </p>

            <p class="form-row form-row-wide">
                <label for="ci_account"><?php esc_html_e( 'Últimos 6 Dígitos de la Cuenta Origen', 'woocommerce-megasoft-gateway-v2' ); ?> <span class="required">*</span></label>
                <input type="text" id="ci_account" name="ci_account" placeholder="123456" maxlength="6" minlength="6" pattern="[0-9]{6}" required />
                <small><?php esc_html_e( 'Solo los últimos 6 dígitos de tu cuenta bancaria', 'woocommerce-megasoft-gateway-v2' ); ?></small>
            </p>

            <p class="form-row form-row-wide">
                <label for="ci_phone"><?php esc_html_e( 'Teléfono', 'woocommerce-megasoft-gateway-v2' ); ?> <span class="required">*</span></label>
                <input type="text" id="ci_phone" name="ci_phone" placeholder="04141234567" maxlength="11" pattern="[0-9]{11}" required />
            </p>

            <p class="form-row form-row-wide">
                <label for="ci_bank"><?php esc_html_e( 'Banco Origen', 'woocommerce-megasoft-gateway-v2' ); ?> <span class="required">*</span></label>
                <select id="ci_bank" name="ci_bank" required>
                    <?php foreach ( $this->get_venezuelan_banks() as $code => $name ) : ?>
                        <option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
        </fieldset>
        <?php
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        try {
            // Get form data
            $doc_type   = sanitize_text_field( $_POST['ci_doc_type'] ?? 'V' );
            $doc_number = sanitize_text_field( $_POST['ci_doc_number'] ?? '' );
            $account    = sanitize_text_field( $_POST['ci_account'] ?? '' );
            $phone      = sanitize_text_field( $_POST['ci_phone'] ?? '' );
            $bank       = sanitize_text_field( $_POST['ci_bank'] ?? '' );

            // Validate
            if ( empty( $doc_number ) || empty( $account ) || empty( $phone ) || empty( $bank ) ) {
                throw new Exception( __( 'Todos los campos son requeridos', 'woocommerce-megasoft-gateway-v2' ) );
            }

            // Get merchant settings
            $destination_account = $this->get_option( 'destination_account' );

            if ( empty( $destination_account ) ) {
                throw new Exception( __( 'Configuración del comercio incompleta. Contacte al administrador.', 'woocommerce-megasoft-gateway-v2' ) );
            }

            // Pre-registro
            $preregistro = $this->api->preregistro();

            if ( is_wp_error( $preregistro ) ) {
                throw new Exception( $preregistro->get_error_message() );
            }

            if ( ! $preregistro['success'] ) {
                throw new Exception( $preregistro['message'] ?? __( 'Error en pre-registro', 'woocommerce-megasoft-gateway-v2' ) );
            }

            $control = $preregistro['control'];
            $order->update_meta_data( '_megasoft_v2_control', $control );
            $order->update_meta_data( '_megasoft_v2_customer_cid', $doc_type . $doc_number );
            $order->update_meta_data( '_megasoft_v2_customer_account', $account );
            $order->update_meta_data( '_megasoft_v2_customer_phone', $phone );
            $order->update_meta_data( '_megasoft_v2_customer_bank', $bank );
            $order->save();

            // Procesar Crédito Inmediato
            $payment_data = array(
                'control'           => $control,
                'cid'               => $doc_type . $doc_number,
                'cuentaOrigen'      => $account,
                'telefonoOrigen'    => $phone,
                'codigobancoOrigen' => $bank,
                'cuentaDestino'     => $destination_account,
                'amount'            => $order->get_total(),
                'factura'           => $order->get_order_number(),
            );

            $response = $this->api->procesar_compra_creditoinmediato( $payment_data );

            if ( is_wp_error( $response ) ) {
                throw new Exception( $response->get_error_message() );
            }

            // NOTE: Do NOT throw exception if response['success'] is false
            // success=false just means payment was rejected
            // We need to continue to query_status to get voucher data and show it

            // Query status
            $status = $this->api->query_status( $control, 'CREDITOINMEDIATO' );

            if ( is_wp_error( $status ) ) {
                throw new Exception( $status->get_error_message() );
            }

            // Prepare transaction data for voucher
            $transaction_data = array(
                'type'           => 'Crédito Inmediato',
                'amount'         => $order->get_total(),
                'currency'       => $order->get_currency(),
                'account'        => $account,
                'phone'          => $phone,
                'bank'           => $bank,
                'merchant_info'  => array(
                    'name'    => get_bloginfo( 'name' ),
                    'address' => get_option( 'woocommerce_store_address' ),
                ),
            );

            // Generate and save voucher regardless of status
            $this->generate_and_save_voucher( $order, $transaction_data, $status );

            if ( $status['success'] && $status['codigo'] === '00' ) {
                // Payment approved
                $order->payment_complete( $control );
                $order->add_order_note( sprintf( __( 'Crédito Inmediato procesado. Control: %s', 'woocommerce-megasoft-gateway-v2' ), $control ) );

                // Clear cart only on success
                WC()->cart->empty_cart();
            } else {
                // Payment rejected - mark as failed but still redirect to show voucher
                $error_message = $status['mensaje'] ?? __( 'Pago rechazado', 'woocommerce-megasoft-gateway-v2' );
                $order->update_status( 'failed', sprintf( __( 'Pago rechazado. Código: %s - %s', 'woocommerce-megasoft-gateway-v2' ), $status['codigo'], $error_message ) );

                // Add error notice that will be displayed on thank you page
                wc_add_notice( $error_message, 'error' );
            }

            // Always redirect to thank you page to show voucher
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            );

        } catch ( Exception $e ) {
            // Only catch exceptions from API errors, not payment rejection
            wc_add_notice( $e->getMessage(), 'error' );
            return array(
                'result'   => 'failure',
                'redirect' => '',
            );
        }
    }

    /**
     * Generate and save voucher for transaction
     */
    private function generate_and_save_voucher( $order, $transaction_data, $response ) {
        try {
            $voucher_html = MegaSoft_V2_Voucher::generate( $transaction_data, $response );
            MegaSoft_V2_Voucher::save_to_order( $order->get_id(), $voucher_html );

            $this->logger->debug( 'Voucher Crédito Inmediato generado y guardado', array(
                'order_id' => $order->get_id(),
            ) );
        } catch ( Exception $e ) {
            $this->logger->error( 'Error al generar voucher Crédito Inmediato', array(
                'order_id' => $order->get_id(),
                'error'    => $e->getMessage(),
            ) );
        }
    }

    /**
     * Display thank you page with voucher
     */
    public function thankyou_page( $order_id ) {
        if ( ! $order_id ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Display voucher using the voucher class
        MegaSoft_V2_Voucher::display_in_order_details( $order );
    }

    private function get_venezuelan_banks() {
        return array(
            '0102' => 'Banco de Venezuela',
            '0104' => 'Banco Venezolano de Crédito',
            '0105' => 'Banco Mercantil',
            '0108' => 'Banco Provincial',
            '0114' => 'Banco del Caribe',
            '0115' => 'Banco Exterior',
            '0128' => 'Banco Caroní',
            '0134' => 'Banesco',
            '0137' => 'Banco Sofitasa',
            '0138' => 'Banco Plaza',
            '0146' => 'Bangente',
            '0151' => 'Banco Fondo Común (BFC)',
            '0156' => '100% Banco',
            '0157' => 'Banco del Sur',
            '0163' => 'Banco del Tesoro',
            '0166' => 'Banco Agrícola de Venezuela',
            '0168' => 'Bancrecer',
            '0169' => 'Banco Activo',
            '0171' => 'Banco Activo',
            '0172' => 'Bancamiga',
            '0173' => 'Banco Internacional de Desarrollo',
            '0174' => 'Banplus',
            '0175' => 'Banco Bicentenario',
            '0177' => 'Banco de la Fuerza Armada Nacional Bolivariana',
            '0191' => 'Banco Nacional de Crédito (BNC)',
        );
    }
}
