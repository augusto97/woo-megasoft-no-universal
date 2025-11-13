<?php
/**
 * MegaSoft Gateway v2 - Main Payment Gateway Class
 * NON-UNIVERSAL MODE - Direct Card Capture
 *
 * Extends WooCommerce Payment Gateway to provide direct card capture
 * without redirecting users to external pages.
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_MegaSoft_V2 extends WC_Payment_Gateway {

    /**
     * @var MegaSoft_V2_API
     */
    private $api;

    /**
     * @var MegaSoft_V2_Logger
     */
    private $logger;

    /**
     * @var bool
     */
    private $testmode;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id                 = 'megasoft_v2';
        $this->icon               = apply_filters( 'woocommerce_megasoft_v2_icon', '' );
        $this->has_fields         = true;
        $this->method_title       = __( 'Mega Soft v2 (Direct Capture)', 'woocommerce-megasoft-gateway-v2' );
        $this->method_description = __( 'Permite a los clientes pagar directamente con tarjeta de crédito/débito sin redirección. Requiere certificación PCI DSS.', 'woocommerce-megasoft-gateway-v2' );

        // Supports
        $this->supports = array(
            'products',
            'refunds',
        );

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        // Get settings
        $this->enabled              = $this->get_option( 'enabled' );
        $this->title                = $this->get_option( 'title' );
        $this->description          = $this->get_option( 'description' );
        $this->testmode             = 'yes' === $this->get_option( 'testmode' );
        $this->api_user             = $this->get_option( 'api_user' );
        $this->api_password         = $this->get_option( 'api_password' );
        $this->cod_afiliacion       = $this->get_option( 'cod_afiliacion' );
        $this->debug                = 'yes' === $this->get_option( 'debug' );
        $this->card_types           = $this->get_option( 'card_types', array( 'visa', 'mastercard' ) );
        $this->auto_capture         = 'yes' === $this->get_option( 'auto_capture', 'yes' );
        $this->save_cards           = 'yes' === $this->get_option( 'save_cards', 'no' );

        // Initialize API and Logger
        $this->api = new MegaSoft_V2_API(
            $this->cod_afiliacion,
            $this->api_user,
            $this->api_password,
            $this->testmode
        );

        $this->logger = new MegaSoft_V2_Logger(
            $this->debug,
            $this->get_option( 'log_level', 'info' )
        );

        // Hooks
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
        add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

        // AJAX handlers for frontend validation
        add_action( 'wp_ajax_megasoft_v2_validate_card', array( $this, 'ajax_validate_card' ) );
        add_action( 'wp_ajax_nopriv_megasoft_v2_validate_card', array( $this, 'ajax_validate_card' ) );
    }

    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Habilitar/Deshabilitar', 'woocommerce-megasoft-gateway-v2' ),
                'type'    => 'checkbox',
                'label'   => __( 'Habilitar Mega Soft Gateway v2', 'woocommerce-megasoft-gateway-v2' ),
                'default' => 'no',
            ),
            'title' => array(
                'title'       => __( 'Título', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'text',
                'description' => __( 'Título que el usuario ve durante el checkout.', 'woocommerce-megasoft-gateway-v2' ),
                'default'     => __( 'Tarjeta de Crédito/Débito', 'woocommerce-megasoft-gateway-v2' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Descripción', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'textarea',
                'description' => __( 'Descripción que el usuario ve durante el checkout.', 'woocommerce-megasoft-gateway-v2' ),
                'default'     => __( 'Paga de forma segura con tu tarjeta de crédito o débito.', 'woocommerce-megasoft-gateway-v2' ),
                'desc_tip'    => true,
            ),
            'testmode' => array(
                'title'   => __( 'Modo de Prueba', 'woocommerce-megasoft-gateway-v2' ),
                'type'    => 'checkbox',
                'label'   => __( 'Habilitar modo de prueba', 'woocommerce-megasoft-gateway-v2' ),
                'default' => 'yes',
                'description' => __( 'Usa el ambiente de pruebas de Mega Soft (paytest.megasoft.com.ve).', 'woocommerce-megasoft-gateway-v2' ),
            ),
            'api_credentials' => array(
                'title'       => __( 'Credenciales API', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'title',
                'description' => __( 'Ingresa tus credenciales proporcionadas por Mega Soft.', 'woocommerce-megasoft-gateway-v2' ),
            ),
            'api_user' => array(
                'title'       => __( 'Usuario API', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'text',
                'description' => __( 'Usuario para autenticación Basic Auth.', 'woocommerce-megasoft-gateway-v2' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'api_password' => array(
                'title'       => __( 'Contraseña API', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'password',
                'description' => __( 'Contraseña para autenticación Basic Auth.', 'woocommerce-megasoft-gateway-v2' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'cod_afiliacion' => array(
                'title'       => __( 'Código de Afiliación', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'text',
                'description' => __( 'Código de afiliación proporcionado por Mega Soft (ej: 1234567).', 'woocommerce-megasoft-gateway-v2' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'card_settings' => array(
                'title'       => __( 'Configuración de Tarjetas', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'title',
                'description' => '',
            ),
            'card_types' => array(
                'title'       => __( 'Tarjetas Aceptadas', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'multiselect',
                'description' => __( 'Selecciona los tipos de tarjetas que aceptas.', 'woocommerce-megasoft-gateway-v2' ),
                'default'     => array( 'visa', 'mastercard' ),
                'options'     => array(
                    'visa'       => 'Visa',
                    'mastercard' => 'MasterCard',
                    'amex'       => 'American Express',
                    'discover'   => 'Discover',
                    'diners'     => 'Diners Club',
                ),
                'desc_tip'    => true,
            ),
            'auto_capture' => array(
                'title'   => __( 'Captura Automática', 'woocommerce-megasoft-gateway-v2' ),
                'type'    => 'checkbox',
                'label'   => __( 'Capturar pagos automáticamente', 'woocommerce-megasoft-gateway-v2' ),
                'default' => 'yes',
                'description' => __( 'Si está habilitado, los pagos se capturarán automáticamente. Si está deshabilitado, deberás capturar manualmente desde el admin.', 'woocommerce-megasoft-gateway-v2' ),
            ),
            'save_cards' => array(
                'title'   => __( 'Guardar Tarjetas', 'woocommerce-megasoft-gateway-v2' ),
                'type'    => 'checkbox',
                'label'   => __( 'Permitir a clientes guardar tarjetas', 'woocommerce-megasoft-gateway-v2' ),
                'default' => 'no',
                'description' => __( 'ADVERTENCIA PCI: Requiere cumplimiento completo de PCI DSS SAQ-D.', 'woocommerce-megasoft-gateway-v2' ),
            ),
            'advanced' => array(
                'title'       => __( 'Configuración Avanzada', 'woocommerce-megasoft-gateway-v2' ),
                'type'        => 'title',
                'description' => '',
            ),
            'debug' => array(
                'title'   => __( 'Modo Debug', 'woocommerce-megasoft-gateway-v2' ),
                'type'    => 'checkbox',
                'label'   => __( 'Habilitar logs detallados', 'woocommerce-megasoft-gateway-v2' ),
                'default' => 'yes',
                'description' => sprintf(
                    __( 'Los logs se guardarán en %s', 'woocommerce-megasoft-gateway-v2' ),
                    '<code>' . WC_Log_Handler_File::get_log_file_path( 'megasoft-v2' ) . '</code>'
                ),
            ),
            'log_level' => array(
                'title'   => __( 'Nivel de Log', 'woocommerce-megasoft-gateway-v2' ),
                'type'    => 'select',
                'default' => 'info',
                'options' => array(
                    'debug' => __( 'Debug (Todos los mensajes)', 'woocommerce-megasoft-gateway-v2' ),
                    'info'  => __( 'Info (Información importante)', 'woocommerce-megasoft-gateway-v2' ),
                    'warn'  => __( 'Warning (Advertencias)', 'woocommerce-megasoft-gateway-v2' ),
                    'error' => __( 'Error (Solo errores)', 'woocommerce-megasoft-gateway-v2' ),
                ),
            ),
        );
    }

    /**
     * Load payment scripts and styles
     */
    public function payment_scripts() {
        // Only load on checkout page
        if ( ! is_checkout() ) {
            return;
        }

        // Only load if gateway is enabled
        if ( 'no' === $this->enabled ) {
            return;
        }

        // Card validation library (Luhn algorithm, card type detection)
        wp_enqueue_script(
            'megasoft-v2-card-validator',
            plugins_url( 'assets/js/card-validator.js', MEGASOFT_V2_PLUGIN_FILE ),
            array( 'jquery' ),
            MEGASOFT_V2_VERSION,
            true
        );

        // Main payment form script
        wp_enqueue_script(
            'megasoft-v2-payment',
            plugins_url( 'assets/js/payment-form.js', MEGASOFT_V2_PLUGIN_FILE ),
            array( 'jquery', 'megasoft-v2-card-validator' ),
            MEGASOFT_V2_VERSION,
            true
        );

        // Styles
        wp_enqueue_style(
            'megasoft-v2-payment',
            plugins_url( 'assets/css/payment-form.css', MEGASOFT_V2_PLUGIN_FILE ),
            array(),
            MEGASOFT_V2_VERSION
        );

        // Localize script
        wp_localize_script(
            'megasoft-v2-payment',
            'megasoftV2Params',
            array(
                'ajax_url'           => admin_url( 'admin-ajax.php' ),
                'nonce'              => wp_create_nonce( 'megasoft_v2_payment' ),
                'card_types'         => $this->card_types,
                'testmode'           => $this->testmode,
                'i18n' => array(
                    'invalid_card'   => __( 'Número de tarjeta inválido', 'woocommerce-megasoft-gateway-v2' ),
                    'invalid_expiry' => __( 'Fecha de expiración inválida', 'woocommerce-megasoft-gateway-v2' ),
                    'invalid_cvv'    => __( 'CVV inválido', 'woocommerce-megasoft-gateway-v2' ),
                    'invalid_doc'    => __( 'Documento inválido', 'woocommerce-megasoft-gateway-v2' ),
                    'processing'     => __( 'Procesando pago...', 'woocommerce-megasoft-gateway-v2' ),
                ),
            )
        );
    }

    /**
     * Display payment fields on checkout page
     */
    public function payment_fields() {
        // Description
        if ( $this->description ) {
            echo wpautop( wp_kses_post( $this->description ) );
        }

        // Testmode notice
        if ( $this->testmode ) {
            echo '<p class="megasoft-v2-testmode-notice">' .
                __( 'MODO DE PRUEBA HABILITADO. No se realizarán cargos reales.', 'woocommerce-megasoft-gateway-v2' ) .
                '</p>';
        }

        // PCI Notice
        echo '<div class="megasoft-v2-pci-notice">';
        echo '<small>' . __( 'Tu información está protegida con encriptación SSL y cumplimos con PCI DSS.', 'woocommerce-megasoft-gateway-v2' ) . '</small>';
        echo '</div>';

        // Payment form
        $this->render_payment_form();
    }

    /**
     * Render the payment form HTML
     */
    private function render_payment_form() {
        ?>
        <fieldset id="megasoft-v2-payment-form" class="megasoft-v2-payment-form">

            <!-- Card Number -->
            <p class="form-row form-row-wide">
                <label for="megasoft_v2_card_number">
                    <?php esc_html_e( 'Número de Tarjeta', 'woocommerce-megasoft-gateway-v2' ); ?>
                    <span class="required">*</span>
                </label>
                <input
                    id="megasoft_v2_card_number"
                    name="megasoft_v2_card_number"
                    type="text"
                    maxlength="19"
                    autocomplete="cc-number"
                    placeholder="1234 5678 9012 3456"
                    class="input-text"
                    inputmode="numeric"
                />
                <span class="megasoft-v2-card-icon"></span>
            </p>

            <!-- Cardholder Name -->
            <p class="form-row form-row-wide">
                <label for="megasoft_v2_card_name">
                    <?php esc_html_e( 'Nombre en la Tarjeta', 'woocommerce-megasoft-gateway-v2' ); ?>
                    <span class="required">*</span>
                </label>
                <input
                    id="megasoft_v2_card_name"
                    name="megasoft_v2_card_name"
                    type="text"
                    autocomplete="cc-name"
                    placeholder="<?php esc_attr_e( 'Nombre como aparece en la tarjeta', 'woocommerce-megasoft-gateway-v2' ); ?>"
                    class="input-text"
                />
            </p>

            <!-- Expiry Date and CVV -->
            <div class="megasoft-v2-card-details">
                <p class="form-row form-row-first">
                    <label for="megasoft_v2_card_expiry">
                        <?php esc_html_e( 'Fecha de Expiración (MM/AA)', 'woocommerce-megasoft-gateway-v2' ); ?>
                        <span class="required">*</span>
                    </label>
                    <input
                        id="megasoft_v2_card_expiry"
                        name="megasoft_v2_card_expiry"
                        type="text"
                        maxlength="5"
                        autocomplete="cc-exp"
                        placeholder="MM/AA"
                        class="input-text"
                        inputmode="numeric"
                    />
                </p>

                <p class="form-row form-row-last">
                    <label for="megasoft_v2_card_cvv">
                        <?php esc_html_e( 'CVV', 'woocommerce-megasoft-gateway-v2' ); ?>
                        <span class="required">*</span>
                    </label>
                    <input
                        id="megasoft_v2_card_cvv"
                        name="megasoft_v2_card_cvv"
                        type="text"
                        maxlength="4"
                        autocomplete="cc-csc"
                        placeholder="123"
                        class="input-text"
                        inputmode="numeric"
                    />
                </p>
            </div>

            <!-- Document Type and Number (Venezuelan requirement) -->
            <div class="megasoft-v2-document-info">
                <p class="form-row form-row-first">
                    <label for="megasoft_v2_doc_type">
                        <?php esc_html_e( 'Tipo de Documento', 'woocommerce-megasoft-gateway-v2' ); ?>
                        <span class="required">*</span>
                    </label>
                    <select
                        id="megasoft_v2_doc_type"
                        name="megasoft_v2_doc_type"
                        class="select"
                    >
                        <option value="V"><?php esc_html_e( 'V - Venezolano', 'woocommerce-megasoft-gateway-v2' ); ?></option>
                        <option value="E"><?php esc_html_e( 'E - Extranjero', 'woocommerce-megasoft-gateway-v2' ); ?></option>
                        <option value="J"><?php esc_html_e( 'J - Jurídico', 'woocommerce-megasoft-gateway-v2' ); ?></option>
                        <option value="G"><?php esc_html_e( 'G - Gubernamental', 'woocommerce-megasoft-gateway-v2' ); ?></option>
                        <option value="P"><?php esc_html_e( 'P - Pasaporte', 'woocommerce-megasoft-gateway-v2' ); ?></option>
                        <option value="C"><?php esc_html_e( 'C - Consorcio', 'woocommerce-megasoft-gateway-v2' ); ?></option>
                    </select>
                </p>

                <p class="form-row form-row-last">
                    <label for="megasoft_v2_doc_number">
                        <?php esc_html_e( 'Número de Documento', 'woocommerce-megasoft-gateway-v2' ); ?>
                        <span class="required">*</span>
                    </label>
                    <input
                        id="megasoft_v2_doc_number"
                        name="megasoft_v2_doc_number"
                        type="text"
                        maxlength="20"
                        placeholder="12345678"
                        class="input-text"
                        inputmode="numeric"
                    />
                </p>
            </div>

            <!-- Card Type (Credit/Debit) -->
            <p class="form-row form-row-wide">
                <label for="megasoft_v2_card_type">
                    <?php esc_html_e( 'Tipo de Tarjeta', 'woocommerce-megasoft-gateway-v2' ); ?>
                    <span class="required">*</span>
                </label>
                <select
                    id="megasoft_v2_card_type"
                    name="megasoft_v2_card_type"
                    class="select"
                >
                    <option value="CREDITO"><?php esc_html_e( 'Crédito', 'woocommerce-megasoft-gateway-v2' ); ?></option>
                    <option value="DEBITO"><?php esc_html_e( 'Débito', 'woocommerce-megasoft-gateway-v2' ); ?></option>
                </select>
            </p>

            <!-- Hidden field for nonce -->
            <input type="hidden" name="megasoft_v2_nonce" value="<?php echo esc_attr( wp_create_nonce( 'megasoft_v2_payment' ) ); ?>" />

        </fieldset>
        <?php
    }

    /**
     * Validate payment fields
     */
    public function validate_fields() {
        $errors = new WP_Error();

        // Validate nonce
        if ( ! isset( $_POST['megasoft_v2_nonce'] ) || ! wp_verify_nonce( $_POST['megasoft_v2_nonce'], 'megasoft_v2_payment' ) ) {
            $errors->add( 'nonce', __( 'Error de seguridad. Por favor recarga la página.', 'woocommerce-megasoft-gateway-v2' ) );
        }

        // Card number
        $card_number = $this->sanitize_card_number( $_POST['megasoft_v2_card_number'] ?? '' );
        if ( empty( $card_number ) ) {
            $errors->add( 'card_number', __( 'El número de tarjeta es requerido.', 'woocommerce-megasoft-gateway-v2' ) );
        } elseif ( ! $this->validate_luhn( $card_number ) ) {
            $errors->add( 'card_number', __( 'El número de tarjeta no es válido.', 'woocommerce-megasoft-gateway-v2' ) );
        }

        // Cardholder name
        $card_name = sanitize_text_field( $_POST['megasoft_v2_card_name'] ?? '' );
        if ( empty( $card_name ) ) {
            $errors->add( 'card_name', __( 'El nombre del titular es requerido.', 'woocommerce-megasoft-gateway-v2' ) );
        }

        // Expiry date
        $card_expiry = sanitize_text_field( $_POST['megasoft_v2_card_expiry'] ?? '' );
        if ( empty( $card_expiry ) || ! preg_match( '/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $card_expiry ) ) {
            $errors->add( 'card_expiry', __( 'La fecha de expiración debe estar en formato MM/AA.', 'woocommerce-megasoft-gateway-v2' ) );
        } else {
            // Check if expired
            list( $exp_month, $exp_year ) = explode( '/', $card_expiry );
            $exp_year = '20' . $exp_year;
            if ( ! $this->is_card_not_expired( $exp_month, $exp_year ) ) {
                $errors->add( 'card_expiry', __( 'La tarjeta ha expirado.', 'woocommerce-megasoft-gateway-v2' ) );
            }
        }

        // CVV
        $card_cvv = sanitize_text_field( $_POST['megasoft_v2_card_cvv'] ?? '' );
        if ( empty( $card_cvv ) || ! preg_match( '/^[0-9]{3,4}$/', $card_cvv ) ) {
            $errors->add( 'card_cvv', __( 'El CVV debe tener 3 o 4 dígitos.', 'woocommerce-megasoft-gateway-v2' ) );
        }

        // Document number
        $doc_number = sanitize_text_field( $_POST['megasoft_v2_doc_number'] ?? '' );
        if ( empty( $doc_number ) ) {
            $errors->add( 'doc_number', __( 'El número de documento es requerido.', 'woocommerce-megasoft-gateway-v2' ) );
        }

        // If there are errors, display them
        if ( ! empty( $errors->get_error_codes() ) ) {
            foreach ( $errors->get_error_messages() as $message ) {
                wc_add_notice( $message, 'error' );
            }
            return false;
        }

        return true;
    }

    /**
     * Process the payment
     *
     * @param int $order_id Order ID
     * @return array Redirect data
     */
    public function process_payment( $order_id ) {
        global $wpdb;

        $order = wc_get_order( $order_id );

        $this->logger->info( 'Iniciando procesamiento de pago', array(
            'order_id' => $order_id,
            'amount'   => $order->get_total(),
        ) );

        try {
            // Get card data from POST
            $card_data = $this->get_card_data_from_post();

            // Step 1: Pre-registro to get control number
            $this->logger->debug( 'Llamando a pre-registro', array( 'order_id' => $order_id ) );

            $preregistro_response = $this->api->preregistro();

            if ( ! $preregistro_response['success'] ) {
                throw new Exception( $preregistro_response['message'] ?? __( 'Error en pre-registro', 'woocommerce-megasoft-gateway-v2' ) );
            }

            $control_number = $preregistro_response['control'];

            $this->logger->info( 'Pre-registro exitoso', array(
                'order_id' => $order_id,
                'control'  => $control_number,
            ) );

            // Save control number to order meta
            $order->update_meta_data( '_megasoft_v2_control', $control_number );
            $order->save();

            // Step 2: Process payment based on card type
            $card_type = sanitize_text_field( $_POST['megasoft_v2_card_type'] ?? 'CREDITO' );

            $payment_data = array(
                'control'    => $control_number,
                'monto'      => $order->get_total(),
                'pan'        => $card_data['number'],
                'cvv2'       => $card_data['cvv'],
                'expdate'    => $card_data['expiry_yyyymm'],
                'titular'    => $card_data['name'],
                'tipodoc'    => $card_data['doc_type'],
                'documento'  => $card_data['doc_number'],
                'email'      => $order->get_billing_email(),
                'telefono'   => $order->get_billing_phone(),
            );

            $this->logger->debug( 'Procesando pago', array(
                'order_id'  => $order_id,
                'control'   => $control_number,
                'card_type' => $card_type,
            ) );

            // Call appropriate API method
            if ( $card_type === 'DEBITO' ) {
                $payment_response = $this->api->procesar_compra_credito( $payment_data, 'DEBITO' );
            } else {
                $payment_response = $this->api->procesar_compra_credito( $payment_data, 'CREDITO' );
            }

            // Step 3: Check response
            if ( ! $payment_response['success'] ) {
                throw new Exception( $payment_response['message'] ?? __( 'Error al procesar el pago', 'woocommerce-megasoft-gateway-v2' ) );
            }

            // Step 4: Query status to confirm
            $this->logger->debug( 'Consultando estado de transacción', array(
                'order_id' => $order_id,
                'control'  => $control_number,
            ) );

            $status_response = $this->api->query_status( $control_number, $card_type );

            if ( ! $status_response['success'] ) {
                throw new Exception( $status_response['message'] ?? __( 'Error al consultar estado', 'woocommerce-megasoft-gateway-v2' ) );
            }

            // Check if approved
            $response_code = $status_response['codigo'] ?? '';

            if ( $response_code === '00' ) {
                // Payment approved!
                $this->logger->info( 'Pago aprobado', array(
                    'order_id' => $order_id,
                    'control'  => $control_number,
                    'codigo'   => $response_code,
                ) );

                // Save transaction data
                $this->save_transaction_data( $order, $status_response, $card_data );

                // Mark order as processing/completed
                $order->payment_complete( $control_number );

                $order->add_order_note(
                    sprintf(
                        __( 'Pago procesado exitosamente. Control: %s, Autorización: %s', 'woocommerce-megasoft-gateway-v2' ),
                        $control_number,
                        $status_response['autorizacion'] ?? 'N/A'
                    )
                );

                // Clear cart
                WC()->cart->empty_cart();

                // Return success with redirect to thank you page
                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url( $order ),
                );

            } else {
                // Payment declined
                $error_message = $status_response['mensaje'] ?? __( 'Pago rechazado', 'woocommerce-megasoft-gateway-v2' );

                $this->logger->warn( 'Pago rechazado', array(
                    'order_id' => $order_id,
                    'control'  => $control_number,
                    'codigo'   => $response_code,
                    'mensaje'  => $error_message,
                ) );

                $order->update_status( 'failed', sprintf( __( 'Pago rechazado. Código: %s - %s', 'woocommerce-megasoft-gateway-v2' ), $response_code, $error_message ) );

                throw new Exception( $error_message );
            }

        } catch ( Exception $e ) {
            $this->logger->error( 'Error al procesar pago', array(
                'order_id' => $order_id,
                'error'    => $e->getMessage(),
            ) );

            wc_add_notice( __( 'Error: ', 'woocommerce-megasoft-gateway-v2' ) . $e->getMessage(), 'error' );

            return array(
                'result'   => 'failure',
                'redirect' => '',
            );
        }
    }

    /**
     * Get card data from POST and sanitize
     *
     * @return array Card data
     */
    private function get_card_data_from_post() {
        $card_number = $this->sanitize_card_number( $_POST['megasoft_v2_card_number'] ?? '' );
        $card_expiry = sanitize_text_field( $_POST['megasoft_v2_card_expiry'] ?? '' );

        // Convert MM/YY to YYYYMM
        list( $exp_month, $exp_year ) = explode( '/', $card_expiry );
        $expiry_yyyymm = '20' . $exp_year . $exp_month;

        return array(
            'number'         => $card_number,
            'name'           => sanitize_text_field( $_POST['megasoft_v2_card_name'] ?? '' ),
            'cvv'            => sanitize_text_field( $_POST['megasoft_v2_card_cvv'] ?? '' ),
            'expiry'         => $card_expiry,
            'expiry_yyyymm'  => $expiry_yyyymm,
            'doc_type'       => sanitize_text_field( $_POST['megasoft_v2_doc_type'] ?? 'V' ),
            'doc_number'     => sanitize_text_field( $_POST['megasoft_v2_doc_number'] ?? '' ),
        );
    }

    /**
     * Save transaction data to database
     *
     * @param WC_Order $order Order object
     * @param array $response API response
     * @param array $card_data Card data (sanitized)
     */
    private function save_transaction_data( $order, $response, $card_data ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_v2_transactions';

        // Get last 4 digits of card
        $last_four = substr( $card_data['number'], -4 );

        $wpdb->insert(
            $table_name,
            array(
                'order_id'             => $order->get_id(),
                'control_number'       => $response['control'] ?? '',
                'authorization_code'   => $response['autorizacion'] ?? '',
                'transaction_type'     => sanitize_text_field( $_POST['megasoft_v2_card_type'] ?? 'CREDITO' ),
                'amount'               => $order->get_total(),
                'currency'             => $order->get_currency(),
                'card_last_four'       => $last_four,
                'card_type'            => $this->detect_card_brand( $card_data['number'] ),
                'response_code'        => $response['codigo'] ?? '',
                'response_message'     => $response['mensaje'] ?? '',
                'transaction_date'     => $response['fecha'] ?? current_time( 'mysql' ),
                'status'               => 'approved',
                'raw_response'         => json_encode( $response ),
                'created_at'           => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        // Save to order meta as well
        $order->update_meta_data( '_megasoft_v2_authorization', $response['autorizacion'] ?? '' );
        $order->update_meta_data( '_megasoft_v2_card_last_four', $last_four );
        $order->update_meta_data( '_megasoft_v2_card_type', $this->detect_card_brand( $card_data['number'] ) );
        $order->save();
    }

    /**
     * Display receipt page
     *
     * @param int $order_id Order ID
     */
    public function receipt_page( $order_id ) {
        echo '<p>' . __( 'Procesando tu pago...', 'woocommerce-megasoft-gateway-v2' ) . '</p>';
    }

    /**
     * Display thank you page with voucher
     *
     * @param int $order_id Order ID
     */
    public function thankyou_page( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $control = $order->get_meta( '_megasoft_v2_control' );
        $authorization = $order->get_meta( '_megasoft_v2_authorization' );
        $card_last_four = $order->get_meta( '_megasoft_v2_card_last_four' );
        $card_type = $order->get_meta( '_megasoft_v2_card_type' );

        if ( $order->has_status( 'processing' ) || $order->has_status( 'completed' ) ) {
            ?>
            <div class="megasoft-v2-voucher">
                <h2><?php esc_html_e( 'Comprobante de Pago', 'woocommerce-megasoft-gateway-v2' ); ?></h2>

                <div class="voucher-details">
                    <p><strong><?php esc_html_e( 'Número de Control:', 'woocommerce-megasoft-gateway-v2' ); ?></strong> <?php echo esc_html( $control ); ?></p>
                    <p><strong><?php esc_html_e( 'Código de Autorización:', 'woocommerce-megasoft-gateway-v2' ); ?></strong> <?php echo esc_html( $authorization ); ?></p>
                    <p><strong><?php esc_html_e( 'Tarjeta:', 'woocommerce-megasoft-gateway-v2' ); ?></strong> <?php echo esc_html( $card_type ); ?> **** <?php echo esc_html( $card_last_four ); ?></p>
                    <p><strong><?php esc_html_e( 'Monto:', 'woocommerce-megasoft-gateway-v2' ); ?></strong> <?php echo wc_price( $order->get_total() ); ?></p>
                    <p><strong><?php esc_html_e( 'Fecha:', 'woocommerce-megasoft-gateway-v2' ); ?></strong> <?php echo esc_html( $order->get_date_created()->date_i18n( 'd/m/Y H:i:s' ) ); ?></p>
                </div>

                <p class="voucher-print">
                    <button onclick="window.print();" class="button">
                        <?php esc_html_e( 'Imprimir Comprobante', 'woocommerce-megasoft-gateway-v2' ); ?>
                    </button>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Process refund
     *
     * @param int $order_id Order ID
     * @param float|null $amount Refund amount
     * @param string $reason Refund reason
     * @return bool|WP_Error
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return new WP_Error( 'invalid_order', __( 'Orden inválida', 'woocommerce-megasoft-gateway-v2' ) );
        }

        $control = $order->get_meta( '_megasoft_v2_control' );

        if ( empty( $control ) ) {
            return new WP_Error( 'no_control', __( 'No se encontró el número de control', 'woocommerce-megasoft-gateway-v2' ) );
        }

        try {
            $this->logger->info( 'Procesando anulación', array(
                'order_id' => $order_id,
                'control'  => $control,
                'amount'   => $amount,
            ) );

            $response = $this->api->procesar_anulacion( $control, $amount );

            if ( ! $response['success'] ) {
                throw new Exception( $response['message'] ?? __( 'Error al procesar anulación', 'woocommerce-megasoft-gateway-v2' ) );
            }

            $order->add_order_note(
                sprintf(
                    __( 'Anulación procesada. Control: %s, Monto: %s. Razón: %s', 'woocommerce-megasoft-gateway-v2' ),
                    $control,
                    wc_price( $amount ),
                    $reason
                )
            );

            $this->logger->info( 'Anulación exitosa', array(
                'order_id' => $order_id,
                'control'  => $control,
            ) );

            return true;

        } catch ( Exception $e ) {
            $this->logger->error( 'Error en anulación', array(
                'order_id' => $order_id,
                'error'    => $e->getMessage(),
            ) );

            return new WP_Error( 'refund_failed', $e->getMessage() );
        }
    }

    /**
     * Sanitize card number (remove spaces and non-digits)
     *
     * @param string $card_number Card number
     * @return string Sanitized card number
     */
    private function sanitize_card_number( $card_number ) {
        return preg_replace( '/\D/', '', $card_number );
    }

    /**
     * Validate card number using Luhn algorithm
     *
     * @param string $card_number Card number
     * @return bool Valid or not
     */
    private function validate_luhn( $card_number ) {
        $sum = 0;
        $num_digits = strlen( $card_number );
        $parity = $num_digits % 2;

        for ( $i = 0; $i < $num_digits; $i++ ) {
            $digit = (int) $card_number[ $i ];

            if ( $i % 2 === $parity ) {
                $digit *= 2;
            }

            if ( $digit > 9 ) {
                $digit -= 9;
            }

            $sum += $digit;
        }

        return ( $sum % 10 ) === 0;
    }

    /**
     * Check if card is not expired
     *
     * @param string $month Month
     * @param string $year Year
     * @return bool Not expired
     */
    private function is_card_not_expired( $month, $year ) {
        $now = new DateTime();
        $expiry = new DateTime( $year . '-' . $month . '-01' );
        $expiry->modify( 'last day of this month' );

        return $now <= $expiry;
    }

    /**
     * Detect card brand from number
     *
     * @param string $card_number Card number
     * @return string Card brand
     */
    private function detect_card_brand( $card_number ) {
        $patterns = array(
            'visa'       => '/^4/',
            'mastercard' => '/^(5[1-5]|2[2-7])/',
            'amex'       => '/^3[47]/',
            'discover'   => '/^6(?:011|5)/',
            'diners'     => '/^3(?:0[0-5]|[68])/',
        );

        foreach ( $patterns as $brand => $pattern ) {
            if ( preg_match( $pattern, $card_number ) ) {
                return $brand;
            }
        }

        return 'unknown';
    }

    /**
     * AJAX handler for card validation
     */
    public function ajax_validate_card() {
        check_ajax_referer( 'megasoft_v2_payment', 'nonce' );

        $card_number = $this->sanitize_card_number( $_POST['card_number'] ?? '' );

        $response = array(
            'valid' => $this->validate_luhn( $card_number ),
            'brand' => $this->detect_card_brand( $card_number ),
        );

        wp_send_json_success( $response );
    }
}
