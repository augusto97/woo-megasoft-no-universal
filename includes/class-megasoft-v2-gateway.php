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

        // Simple logger for reliable logging
        $this->simple_logger = new MegaSoft_V2_Simple_Logger( $this->debug );

        // Hooks
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
        add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

        // AJAX handlers for frontend validation
        add_action( 'wp_ajax_megasoft_v2_validate_card', array( $this, 'ajax_validate_card' ) );
        add_action( 'wp_ajax_nopriv_megasoft_v2_validate_card', array( $this, 'ajax_validate_card' ) );

        // Email hooks - Add payment info to emails
        add_action( 'woocommerce_email_after_order_table', array( $this, 'add_payment_info_to_email' ), 10, 4 );
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

        $log_file = MEGASOFT_V2_PLUGIN_PATH . 'debug-validation.log';
        $log_msg = "\n[" . date('Y-m-d H:i:s') . "] validate_fields INICIADO\n";
        $log_msg .= "POST data presente:\n";
        $log_msg .= "  - card_number: " . (isset($_POST['megasoft_v2_card_number']) ? 'SI' : 'NO') . "\n";
        $log_msg .= "  - card_name: " . (isset($_POST['megasoft_v2_card_name']) ? 'SI' : 'NO') . "\n";
        $log_msg .= "  - doc_number: " . (isset($_POST['megasoft_v2_doc_number']) ? 'SI' : 'NO') . "\n";
        $log_msg .= "  - nonce: " . (isset($_POST['megasoft_v2_nonce']) ? 'SI' : 'NO') . "\n";

        // Debug logging
        $this->logger->info( 'validate_fields iniciado', array(
            'post_data' => array(
                'card_number_present' => isset( $_POST['megasoft_v2_card_number'] ),
                'card_name_present' => isset( $_POST['megasoft_v2_card_name'] ),
                'doc_number_present' => isset( $_POST['megasoft_v2_doc_number'] ),
            ),
        ) );

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

        // Expiry date - use card validator
        $card_expiry = sanitize_text_field( $_POST['megasoft_v2_card_expiry'] ?? '' );
        if ( empty( $card_expiry ) || ! preg_match( '/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $card_expiry ) ) {
            $errors->add( 'card_expiry', __( 'La fecha de expiración debe estar en formato MM/AA.', 'woocommerce-megasoft-gateway-v2' ) );
        } else {
            // Check if expired using card validator
            list( $exp_month, $exp_year ) = explode( '/', $card_expiry );
            $exp_year = '20' . $exp_year;
            $expiry_validation = MegaSoft_V2_Card_Validator::validate_expiry( $exp_month, $exp_year );
            if ( ! $expiry_validation['valid'] ) {
                $errors->add( 'card_expiry', $expiry_validation['message'] );
            }
        }

        // CVV - use card validator with card brand detection
        $card_cvv = sanitize_text_field( $_POST['megasoft_v2_card_cvv'] ?? '' );
        if ( empty( $card_cvv ) ) {
            $errors->add( 'card_cvv', __( 'El CVV es requerido.', 'woocommerce-megasoft-gateway-v2' ) );
        } else {
            $card_brand = ! empty( $card_number ) ? $this->detect_card_brand( $card_number ) : '';
            $cvv_validation = MegaSoft_V2_Card_Validator::validate_cvv( $card_cvv, $card_brand );
            if ( ! $cvv_validation['valid'] ) {
                $errors->add( 'card_cvv', $cvv_validation['message'] );
            }
        }

        // Document number - use CID validator
        $doc_type = sanitize_text_field( $_POST['megasoft_v2_doc_type'] ?? 'V' );
        $doc_number = sanitize_text_field( $_POST['megasoft_v2_doc_number'] ?? '' );

        // First check if document number is provided
        if ( empty( $doc_number ) ) {
            $errors->add( 'doc_number', __( 'El número de documento es requerido.', 'woocommerce-megasoft-gateway-v2' ) );
        } else {
            // Then validate the full CID format
            $cid = $doc_type . $doc_number;
            $cid_validation = MegaSoft_V2_Card_Validator::validate_cid( $cid );
            if ( ! $cid_validation['valid'] ) {
                $errors->add( 'doc_number', $cid_validation['message'] );
            }
        }

        // If there are errors, display them
        if ( ! empty( $errors->get_error_codes() ) ) {
            // File logging
            $log_file = MEGASOFT_V2_PLUGIN_PATH . 'debug-validation.log';
            $log_msg = "ERRORES DE VALIDACIÓN:\n";
            foreach ( $errors->get_error_messages() as $msg ) {
                $log_msg .= "  - " . $msg . "\n";
            }

            $this->logger->warn( 'Errores de validación encontrados', array(
                'error_codes' => $errors->get_error_codes(),
                'error_messages' => $errors->get_error_messages(),
            ) );

            foreach ( $errors->get_error_messages() as $message ) {
                wc_add_notice( $message, 'error' );
            }
            return false;
        }

        // File logging
        $log_file = MEGASOFT_V2_PLUGIN_PATH . 'debug-validation.log';

        $this->logger->info( 'validate_fields completado exitosamente' );
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

        $log_file = MEGASOFT_V2_PLUGIN_PATH . 'debug-validation.log';

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


            // Combinar tipo de documento + número para formar CID
            $cid = $card_data['doc_type'] . $card_data['doc_number'];

            // Preparar datos según lo que espera la API
            $payment_data = array(
                'control'    => $control_number,
                'pan'        => $card_data['number'],
                'cvv2'       => $card_data['cvv'],
                'cid'        => $cid,
                'expdate'    => $card_data['expiry_yyyymm'],
                'amount'     => $order->get_total(),
                'client'     => $card_data['name'],
                'factura'    => (string) $order->get_order_number(),
            );

            $this->logger->debug( 'Procesando pago', array(
                'order_id'  => $order_id,
                'control'   => $control_number,
                'card_type' => $card_type,
            ) );

            // Call API method (no acepta segundo parámetro)
            $payment_response = $this->api->procesar_compra_credito( $payment_data );

            // Step 3: Check response
            if ( ! isset( $payment_response['success'] ) ) {
                throw new Exception( __( 'Respuesta de API inválida', 'woocommerce-megasoft-gateway-v2' ) );
            }

            // NOTE: Do NOT throw exception if payment_response['success'] is false
            // This happens when card is blocked, insufficient funds, etc.
            // We need to continue to query_status to get the voucher data
            // and then redirect to thank you page to show the voucher

            // Step 4: Query status to confirm and get full details
            $this->logger->debug( 'Consultando estado de transacción', array(
                'order_id' => $order_id,
                'control'  => $control_number,
            ) );

            $status_response = $this->api->query_status( $control_number, $card_type );

            // NOTE: Do NOT throw exception if status_response['success'] is false
            // success=false just means the payment was not approved (blocked card, insufficient funds, etc.)
            // but we still got a valid response with voucher data that we need to show
            // The actual approval status is determined by checking the 'codigo' field

            // Check if approved by codigo (00 = approved, anything else = rejected)
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

                // Generate and save voucher
                $this->generate_and_save_voucher( $order, $card_data, $status_response );

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
                // Payment declined - but still redirect to show voucher
                $error_message = $status_response['mensaje'] ?? __( 'Pago rechazado', 'woocommerce-megasoft-gateway-v2' );

                $this->logger->warn( 'Pago rechazado', array(
                    'order_id' => $order_id,
                    'control'  => $control_number,
                    'codigo'   => $response_code,
                    'mensaje'  => $error_message,
                ) );

                // Generate and save voucher for rejected payment
                $this->generate_and_save_voucher( $order, $card_data, $status_response );

                // Save transaction data even for rejected payments
                $this->save_transaction_data( $order, $status_response, $card_data );

                $order->update_status( 'failed', sprintf( __( 'Pago rechazado. Código: %s - %s', 'woocommerce-megasoft-gateway-v2' ), $response_code, $error_message ) );

                // Add error notice that will be displayed on thank you page
                wc_add_notice( $error_message, 'error' );

                // Return success with redirect to show voucher (do not throw exception)
                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url( $order ),
                );
            }

        } catch ( Exception $e ) {
            $log_file = MEGASOFT_V2_PLUGIN_PATH . 'debug-validation.log';

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

        // Convert MM/YY to MMAA (formato requerido por Mega Soft API)
        list( $exp_month, $exp_year ) = explode( '/', $card_expiry );
        $expiry_mmaa = $exp_month . $exp_year; // Ejemplo: 12/25 -> 1225

        return array(
            'number'         => $card_number,
            'name'           => sanitize_text_field( $_POST['megasoft_v2_card_name'] ?? '' ),
            'cvv'            => sanitize_text_field( $_POST['megasoft_v2_card_cvv'] ?? '' ),
            'expiry'         => $card_expiry,
            'expiry_yyyymm'  => $expiry_mmaa, // Mantener nombre de variable por compatibilidad, pero ahora es MMAA
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
        // Log attempt
        $this->simple_logger->info( 'Intentando guardar transacción', array(
            'order_id' => $order->get_id(),
            'control' => $response['control'] ?? 'N/A',
        ) );

        // Use new simplified transaction saver
        $result = MegaSoft_V2_Transaction_Saver::save( $order, $response, $card_data );

        if ( $result === false ) {
            $this->simple_logger->error( 'Error al guardar transacción', array(
                'order_id' => $order->get_id(),
                'control' => $response['control'] ?? 'N/A',
            ) );

            // Also log to old logger
            $this->logger->error( 'Error al guardar transacción en BD', array(
                'order_id' => $order->get_id(),
            ) );
        } else {
            $this->simple_logger->info( 'Transacción guardada exitosamente', array(
                'order_id' => $order->get_id(),
                'control' => $response['control'] ?? 'N/A',
                'insert_id' => $result,
            ) );

            // Also log to old logger
            $this->logger->info( 'Transacción guardada en BD', array(
                'order_id' => $order->get_id(),
                'control' => $response['control'] ?? '',
            ) );
        }
    }

    /**
     * Generate and save voucher for transaction
     *
     * @param WC_Order $order Order object
     * @param array $card_data Card data (sanitized)
     * @param array $response API response
     */
    private function generate_and_save_voucher( $order, $card_data, $response ) {
        try {
            // Prepare transaction data for voucher
            $transaction_data = array(
                'type'           => sanitize_text_field( $_POST['megasoft_v2_card_type'] ?? 'CREDITO' ),
                'amount'         => $order->get_total(),
                'currency'       => $order->get_currency(),
                'card_last_four' => substr( $card_data['number'], -4 ),
                'merchant_info'  => array(
                    'name'    => get_bloginfo( 'name' ),
                    'address' => get_option( 'woocommerce_store_address' ),
                ),
            );

            // Generate voucher HTML
            $voucher_html = MegaSoft_V2_Voucher::generate( $transaction_data, $response );

            // Save to order meta
            MegaSoft_V2_Voucher::save_to_order( $order->get_id(), $voucher_html );

            $this->logger->debug( 'Voucher generado y guardado', array(
                'order_id' => $order->get_id(),
            ) );

        } catch ( Exception $e ) {
            $this->logger->error( 'Error al generar voucher', array(
                'order_id' => $order->get_id(),
                'error'    => $e->getMessage(),
            ) );
        }
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

        // Display voucher using the voucher class
        MegaSoft_V2_Voucher::display_in_order_details( $order );
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

        // Get transaction data from order meta
        $terminal = $order->get_meta( '_megasoft_v2_terminal' );
        $seqnum = $order->get_meta( '_megasoft_v2_seqnum' );
        $referencia = $order->get_meta( '_megasoft_v2_referencia' );
        $card_last_four = $order->get_meta( '_megasoft_v2_card_last_four' );
        $authid = $order->get_meta( '_megasoft_v2_authorization' );

        try {
            $this->logger->info( 'Procesando anulación', array(
                'order_id' => $order_id,
                'control'  => $control,
                'amount'   => $amount,
            ) );

            // Prepare anulacion data according to API documentation
            $anulacion_data = array(
                'control'    => $control,
                'terminal'   => $terminal,
                'seqnum'     => $seqnum,
                'monto'      => $amount ?? $order->get_total(),
                'factura'    => (string) $order->get_order_number(),
                'referencia' => $referencia,
                'ult'        => $card_last_four,
                'authid'     => $authid,
            );

            $response = $this->api->procesar_anulacion( $anulacion_data );

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

    /**
     * Add payment information to order emails
     *
     * @param WC_Order $order Order object
     * @param bool $sent_to_admin Whether the email is sent to admin
     * @param bool $plain_text Whether the email is plain text
     * @param WC_Email $email Email object
     */
    public function add_payment_info_to_email( $order, $sent_to_admin, $plain_text, $email ) {
        // Only add for Mega Soft orders
        if ( strpos( $order->get_payment_method(), 'megasoft' ) === false ) {
            return;
        }

        // Get payment info
        $control = $order->get_meta( '_megasoft_v2_control' );
        $authorization = $order->get_meta( '_megasoft_v2_authorization' );
        $card_last_four = $order->get_meta( '_megasoft_v2_card_last_four' );
        $card_type = $order->get_meta( '_megasoft_v2_card_type' );
        $transaction_date = $order->get_meta( '_megasoft_v2_transaction_date' );

        // Skip if no payment info
        if ( ! $control && ! $authorization ) {
            return;
        }

        if ( $plain_text ) {
            // Plain text version
            echo "\n" . str_repeat( '=', 50 ) . "\n";
            echo __( 'INFORMACIÓN DE PAGO - MEGA SOFT', 'woocommerce-megasoft-gateway-v2' ) . "\n";
            echo str_repeat( '=', 50 ) . "\n\n";

            if ( $control ) {
                echo __( 'Número de Control:', 'woocommerce-megasoft-gateway-v2' ) . ' ' . $control . "\n";
            }

            if ( $authorization ) {
                echo __( 'Código de Autorización:', 'woocommerce-megasoft-gateway-v2' ) . ' ' . $authorization . "\n";
            }

            if ( $card_last_four ) {
                echo __( 'Tarjeta:', 'woocommerce-megasoft-gateway-v2' ) . ' ' . ucfirst( $card_type ) . ' ****' . $card_last_four . "\n";
            }

            if ( $transaction_date ) {
                echo __( 'Fecha de Transacción:', 'woocommerce-megasoft-gateway-v2' ) . ' ' . $transaction_date . "\n";
            }

            echo "\n";

        } else {
            // HTML version
            ?>
            <div style="margin: 30px 0; padding: 20px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;">
                <h2 style="margin-top: 0; color: #2271b1; font-size: 18px; border-bottom: 2px solid #2271b1; padding-bottom: 10px;">
                    <?php esc_html_e( 'Información de Pago - Mega Soft', 'woocommerce-megasoft-gateway-v2' ); ?>
                </h2>

                <table cellspacing="0" cellpadding="0" style="width: 100%; border: 0;">
                    <?php if ( $control ) : ?>
                        <tr>
                            <td style="padding: 8px 0; font-weight: 600; width: 40%;">
                                <?php esc_html_e( 'Número de Control:', 'woocommerce-megasoft-gateway-v2' ); ?>
                            </td>
                            <td style="padding: 8px 0;">
                                <code style="background: #fff; padding: 4px 8px; border: 1px solid #ddd; border-radius: 3px; font-family: 'Courier New', monospace;">
                                    <?php echo esc_html( $control ); ?>
                                </code>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if ( $authorization ) : ?>
                        <tr>
                            <td style="padding: 8px 0; font-weight: 600;">
                                <?php esc_html_e( 'Código de Autorización:', 'woocommerce-megasoft-gateway-v2' ); ?>
                            </td>
                            <td style="padding: 8px 0;">
                                <code style="background: #fff; padding: 4px 8px; border: 1px solid #ddd; border-radius: 3px; font-family: 'Courier New', monospace;">
                                    <?php echo esc_html( $authorization ); ?>
                                </code>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if ( $card_last_four ) : ?>
                        <tr>
                            <td style="padding: 8px 0; font-weight: 600;">
                                <?php esc_html_e( 'Tarjeta:', 'woocommerce-megasoft-gateway-v2' ); ?>
                            </td>
                            <td style="padding: 8px 0;">
                                <?php echo esc_html( ucfirst( $card_type ) ); ?> ••••<?php echo esc_html( $card_last_four ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if ( $transaction_date ) : ?>
                        <tr>
                            <td style="padding: 8px 0; font-weight: 600;">
                                <?php esc_html_e( 'Fecha de Transacción:', 'woocommerce-megasoft-gateway-v2' ); ?>
                            </td>
                            <td style="padding: 8px 0;">
                                <?php echo esc_html( mysql2date( 'd/m/Y H:i', $transaction_date ) ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>

                <p style="margin: 15px 0 0 0; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; font-size: 13px; color: #856404;">
                    <strong><?php esc_html_e( 'Nota:', 'woocommerce-megasoft-gateway-v2' ); ?></strong>
                    <?php esc_html_e( 'Conserve esta información para cualquier verificación o reclamo relacionado con el pago.', 'woocommerce-megasoft-gateway-v2' ); ?>
                </p>
            </div>
            <?php
        }
    }
}
