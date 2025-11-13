<?php
/**
 * MegaSoft API v2 Class - REST API Modalidad NO UNIVERSAL
 *
 * Implementa todos los endpoints REST v2 según documentación MAET-PAYM-00_JUL.2025
 * Versión Payment Gateway: 4.24
 * Versión VPos: 3.15.3
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MegaSoft_V2_API {

    /**
     * URL base del API (Producción)
     */
    private $api_base_url = 'https://e-payment.megasoft.com.ve/payment/action/';

    /**
     * URL base del API (Pruebas)
     */
    private $api_test_url = 'https://paytest.megasoft.com.ve/payment/action/';

    /**
     * Código de afiliación del comercio
     */
    private $cod_afiliacion;

    /**
     * Usuario API
     */
    private $api_user;

    /**
     * Contraseña API
     */
    private $api_password;

    /**
     * Modo de prueba
     */
    private $testmode;

    /**
     * Logger instance
     */
    private $logger;

    /**
     * Timeout para requests HTTP (segundos)
     */
    private $timeout = 60;

    /**
     * Constructor
     */
    public function __construct( $cod_afiliacion, $api_user, $api_password, $testmode = false ) {
        $this->cod_afiliacion = $cod_afiliacion;
        $this->api_user       = $api_user;
        $this->api_password   = $api_password;
        $this->testmode       = $testmode;

        // Inicializar logger si existe
        if ( class_exists( 'MegaSoft_V2_Logger' ) ) {
            $this->logger = new MegaSoft_V2_Logger( true, 'info' );
        }
    }

    /**
     * Obtener URL base según modo
     */
    private function get_base_url() {
        return $this->testmode ? $this->api_test_url : $this->api_base_url;
    }

    /**
     * Generar header de autenticación Basic Auth
     */
    private function get_auth_header() {
        $credentials = $this->api_user . ':' . $this->api_password;
        return 'Basic ' . base64_encode( $credentials );
    }

    /**
     * Realizar request HTTP POST con XML
     *
     * @param string $endpoint Endpoint sin base URL (ej: 'v2-preregistro')
     * @param string $xml_body Cuerpo XML del request
     * @param int $timeout Timeout personalizado (opcional)
     * @return array|WP_Error Respuesta parseada o error
     */
    private function do_post_request( $endpoint, $xml_body, $timeout = null ) {
        $url = $this->get_base_url() . $endpoint;

        if ( $timeout === null ) {
            $timeout = $this->timeout;
        }

        $headers = array(
            'Authorization' => $this->get_auth_header(),
            'Content-Type'  => 'text/xml',
            'User-Agent'    => 'WooCommerce-MegaSoft-V2/' . MEGASOFT_V2_VERSION,
        );

        $args = array(
            'method'      => 'POST',
            'timeout'     => $timeout,
            'redirection' => 0,
            'httpversion' => '1.1',
            'blocking'    => true,
            'headers'     => $headers,
            'body'        => $xml_body,
            'sslverify'   => true,
        );

        // Log del request (sin datos sensibles)
        if ( $this->logger ) {
            $this->logger->debug( "API Request to: {$endpoint}", array(
                'url' => $url,
                'xml_preview' => substr( $xml_body, 0, 200 ),
            ) );
        }

        $response = wp_remote_post( $url, $args );

        // Manejar errores de conexión
        if ( is_wp_error( $response ) ) {
            if ( $this->logger ) {
                $this->logger->error( "API Request Error: " . $response->get_error_message(), array(
                    'endpoint' => $endpoint,
                    'error_code' => $response->get_error_code(),
                ) );
            }
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = wp_remote_retrieve_body( $response );

        // Log de respuesta
        if ( $this->logger ) {
            $this->logger->debug( "API Response from: {$endpoint}", array(
                'status_code' => $status_code,
                'body_preview' => substr( $body, 0, 300 ),
            ) );
        }

        // Validar código de respuesta HTTP
        if ( $status_code !== 200 ) {
            if ( $this->logger ) {
                $this->logger->warn( "HTTP Status Code no exitoso: {$status_code}" );
            }
        }

        // Parsear XML de respuesta
        $parsed = $this->parse_xml_response( $body );

        if ( is_wp_error( $parsed ) ) {
            if ( $this->logger ) {
                $this->logger->error( "Error parseando XML: " . $parsed->get_error_message() );
            }
            return $parsed;
        }

        return $parsed;
    }

    /**
     * Parsear respuesta XML a array
     */
    private function parse_xml_response( $xml_string ) {
        if ( empty( $xml_string ) ) {
            return new WP_Error( 'empty_response', __( 'Respuesta vacía del servidor', 'woocommerce-megasoft-gateway-v2' ) );
        }

        // Suprimir errores de XML
        libxml_use_internal_errors( true );

        $xml = simplexml_load_string( $xml_string );

        if ( $xml === false ) {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            $error_msg = __( 'Error parseando XML', 'woocommerce-megasoft-gateway-v2' );
            if ( ! empty( $errors ) ) {
                $error_msg .= ': ' . $errors[0]->message;
            }

            return new WP_Error( 'xml_parse_error', $error_msg );
        }

        // Convertir a array
        $json = json_encode( $xml );
        $array = json_decode( $json, true );

        return $array;
    }

    /**
     * Construir XML para request
     */
    private function build_xml( $data ) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<request>' . "\n";

        foreach ( $data as $key => $value ) {
            if ( is_array( $value ) ) {
                // Manejo de arrays anidados si es necesario
                continue;
            }
            $value = htmlspecialchars( $value, ENT_XML1, 'UTF-8' );
            $xml .= "    <{$key}>{$value}</{$key}>\n";
        }

        $xml .= '</request>';

        return $xml;
    }

    // ============================================================================
    // MÉTODOS PRINCIPALES DE LA API REST V2
    // ============================================================================

    /**
     * Pre-registro de transacción
     * Endpoint: v2-preregistro
     *
     * Debe llamarse ANTES de procesar cualquier transacción para obtener
     * el número de control único
     *
     * @return array|WP_Error Array con 'control' y 'codigo', o WP_Error
     */
    public function preregistro() {
        $xml_body = $this->build_xml( array(
            'cod_afiliacion' => $this->cod_afiliacion,
        ) );

        $response = $this->do_post_request( 'v2-preregistro', $xml_body );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Validar respuesta
        if ( ! isset( $response['codigo'] ) ) {
            return new WP_Error( 'invalid_response', __( 'Respuesta inválida de pre-registro', 'woocommerce-megasoft-gateway-v2' ) );
        }

        if ( $response['codigo'] !== '00' ) {
            $desc = isset( $response['descripcion'] ) ? $response['descripcion'] : __( 'Error desconocido', 'woocommerce-megasoft-gateway-v2' );
            return new WP_Error( 'preregistro_failed', $desc );
        }

        return array(
            'success'     => true,
            'control'     => isset( $response['control'] ) ? $response['control'] : '',
            'codigo'      => $response['codigo'],
            'descripcion' => isset( $response['descripcion'] ) ? $response['descripcion'] : '',
        );
    }

    /**
     * Query Status de transacción
     * Endpoint: v2-querystatus
     *
     * Consulta el estado de una transacción previamente registrada
     *
     * @param string $control Número de control
     * @param string $tipotrx Tipo de transacción (CREDITO, DEBITO, C2P, P2C, etc)
     * @param int $version Versión del QueryStatus (2 o 3)
     * @return array|WP_Error
     */
    public function query_status( $control, $tipotrx = 'CREDITO', $version = 3 ) {
        $xml_body = $this->build_xml( array(
            'cod_afiliacion' => $this->cod_afiliacion,
            'control'        => $control,
            'version'        => $version,
            'tipotrx'        => $tipotrx,
        ) );

        $response = $this->do_post_request( 'v2-querystatus', $xml_body, 30 );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return $this->normalize_transaction_response( $response );
    }

    /**
     * Procesar compra con tarjeta de crédito
     * Endpoint: v2-procesar-compra
     *
     * @param array $data Datos de la transacción
     * @return array|WP_Error
     */
    public function procesar_compra_credito( $data ) {
        $required_fields = array( 'control', 'pan', 'cvv2', 'cid', 'expdate', 'amount', 'client' );

        foreach ( $required_fields as $field ) {
            if ( empty( $data[ $field ] ) ) {
                return new WP_Error( 'missing_field', sprintf( __( 'Campo requerido faltante: %s', 'woocommerce-megasoft-gateway-v2' ), $field ) );
            }
        }

        $xml_data = array(
            'cod_afiliacion' => $this->cod_afiliacion,
            'control'        => $data['control'],
            'transcode'      => '0141', // Compra con tarjeta de crédito
            'pan'            => $data['pan'],
            'cvv2'           => $data['cvv2'],
            'cid'            => $data['cid'],
            'expdate'        => $data['expdate'],
            'amount'         => $data['amount'],
            'client'         => $data['client'],
            'factura'        => isset( $data['factura'] ) ? $data['factura'] : '',
            'mode'           => isset( $data['mode'] ) ? $data['mode'] : '4', // 4 = Manual Online Internet
        );

        // Agregar plan si existe (cuotas)
        if ( isset( $data['plan'] ) && !empty( $data['plan'] ) ) {
            $xml_data['plan'] = $data['plan'];
        }

        $xml_body = $this->build_xml( $xml_data );

        $response = $this->do_post_request( 'v2-procesar-compra', $xml_body, 90 );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return $this->normalize_transaction_response( $response );
    }

    /**
     * Procesar Pago Móvil C2P (Comercio a Persona)
     * Endpoint: v2-procesar-compra-c2p
     *
     * @param array $data Datos del pago móvil
     * @return array|WP_Error
     */
    public function procesar_pago_movil_c2p( $data ) {
        $required_fields = array( 'control', 'cid', 'telefono', 'codigobanco', 'codigoc2p', 'amount' );

        foreach ( $required_fields as $field ) {
            if ( empty( $data[ $field ] ) ) {
                return new WP_Error( 'missing_field', sprintf( __( 'Campo requerido faltante: %s', 'woocommerce-megasoft-gateway-v2' ), $field ) );
            }
        }

        $xml_data = array(
            'cod_afiliacion' => $this->cod_afiliacion,
            'control'        => $data['control'],
            'cid'            => $data['cid'],
            'telefono'       => $data['telefono'],
            'codigobanco'    => $data['codigobanco'],
            'codigoc2p'      => $data['codigoc2p'],
            'amount'         => $data['amount'],
            'factura'        => isset( $data['factura'] ) ? $data['factura'] : '',
        );

        $xml_body = $this->build_xml( $xml_data );

        $response = $this->do_post_request( 'v2-procesar-compra-c2p', $xml_body, 90 );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return $this->normalize_transaction_response( $response );
    }

    /**
     * Procesar Pago Móvil P2C (Persona a Comercio)
     * Endpoint: v2-procesar-compra-p2c
     *
     * @param array $data Datos del pago móvil
     * @return array|WP_Error
     */
    public function procesar_pago_movil_p2c( $data ) {
        $required_fields = array( 'control', 'telefonoCliente', 'codigobancoCliente',
                                   'telefonoComercio', 'codigobancoComercio', 'tipoPago', 'amount' );

        foreach ( $required_fields as $field ) {
            if ( empty( $data[ $field ] ) ) {
                return new WP_Error( 'missing_field', sprintf( __( 'Campo requerido faltante: %s', 'woocommerce-megasoft-gateway-v2' ), $field ) );
            }
        }

        $xml_data = array(
            'cod_afiliacion'       => $this->cod_afiliacion,
            'control'              => $data['control'],
            'telefonoCliente'      => $data['telefonoCliente'],
            'codigobancoCliente'   => $data['codigobancoCliente'],
            'telefonoComercio'     => $data['telefonoComercio'],
            'codigobancoComercio'  => $data['codigobancoComercio'],
            'tipoPago'             => $data['tipoPago'],
            'amount'               => $data['amount'],
            'factura'              => isset( $data['factura'] ) ? $data['factura'] : '',
        );

        $xml_body = $this->build_xml( $xml_data );

        $response = $this->do_post_request( 'v2-procesar-compra-p2c', $xml_body, 90 );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return $this->normalize_transaction_response( $response );
    }

    /**
     * Procesar anulación de transacción (Tarjetas de Crédito)
     * Endpoint: v2-procesar-anulacion
     *
     * @param array $data Datos de anulación:
     *   - control: Número de control de la transacción original
     *   - terminal: Terminal de la transacción original
     *   - seqnum: Número de secuencia de la transacción original
     *   - monto: Monto de la transacción original
     *   - factura: Número de factura
     *   - referencia: Referencia de la transacción original
     *   - ult: Últimos 4 dígitos de la tarjeta
     *   - authid: Authorization ID de la transacción original
     * @return array|WP_Error
     */
    public function procesar_anulacion( $data ) {
        $xml_data = array(
            'cod_afiliacion' => $this->cod_afiliacion,
            'control'        => $data['control'],
            'terminal'       => $data['terminal'],
            'seqnum'         => $data['seqnum'],
            'monto'          => $data['monto'],
            'factura'        => $data['factura'],
            'referencia'     => $data['referencia'],
            'ult'            => $data['ult'],
            'authid'         => $data['authid'],
        );

        $xml_body = $this->build_xml( $xml_data );

        $this->log( 'info', 'Procesando anulación', array(
            'control' => $data['control'],
            'terminal' => $data['terminal'],
        ) );

        $response = $this->do_post_request( 'v2-procesar-anulacion', $xml_body, 60 );

        if ( is_wp_error( $response ) ) {
            $this->log( 'error', 'Error en anulación: ' . $response->get_error_message() );
            return $response;
        }

        $this->log( 'info', 'Respuesta de anulación recibida', array( 'codigo' => $response['codigo'] ?? '99' ) );

        return $this->normalize_transaction_response( $response );
    }

    /**
     * Procesar anulación de Pago Móvil C2P
     * Endpoint: v2-procesar-anulacion-c2p
     *
     * @param array $data Datos de anulación:
     *   - control: Número de control
     *   - cid: Identificación del cliente
     *   - telefono: Teléfono del cliente (11 dígitos)
     *   - seqnum: Número de secuencia
     * @return array|WP_Error
     */
    public function procesar_anulacion_c2p( $data ) {
        $xml_data = array(
            'cod_afiliacion' => $this->cod_afiliacion,
            'control'        => $data['control'],
            'cid'            => $data['cid'],
            'telefono'       => $data['telefono'],
            'seqnum'         => $data['seqnum'],
        );

        $xml_body = $this->build_xml( $xml_data );

        $this->log( 'info', 'Procesando anulación C2P', array( 'control' => $data['control'] ) );

        $response = $this->do_post_request( 'v2-procesar-anulacion-c2p', $xml_body, 60 );

        if ( is_wp_error( $response ) ) {
            $this->log( 'error', 'Error en anulación C2P: ' . $response->get_error_message() );
            return $response;
        }

        return $this->normalize_transaction_response( $response );
    }

    /**
     * Procesar cierre de todas las cajas de la afiliación
     * Endpoint: v2-procesar-cierre
     *
     * @return array|WP_Error
     */
    public function procesar_cierre() {
        $xml_data = array(
            'cod_afiliacion' => $this->cod_afiliacion,
        );

        $xml_body = $this->build_xml( $xml_data );

        $this->log( 'info', 'Procesando cierre de cajas', array( 'afiliacion' => $this->cod_afiliacion ) );

        $response = $this->do_post_request( 'v2-procesar-cierre', $xml_body, 90 );

        if ( is_wp_error( $response ) ) {
            $this->log( 'error', 'Error en cierre: ' . $response->get_error_message() );
            return $response;
        }

        // Parse response for cierre (returns vterminales array)
        $success = true;
        $terminals = array();

        if ( isset( $response['vterminales']['vterminal'] ) ) {
            $vterminals = $response['vterminales']['vterminal'];

            // Ensure it's an array
            if ( ! isset( $vterminals[0] ) ) {
                $vterminals = array( $vterminals );
            }

            foreach ( $vterminals as $vterminal ) {
                $terminal_code = $vterminal['codigo'] ?? '99';
                $terminals[] = array(
                    'vtid'        => $vterminal['vtid'] ?? '',
                    'codigo'      => $terminal_code,
                    'descripcion' => $vterminal['descripcion'] ?? '',
                    'seqnum'      => $vterminal['seqnum'] ?? '',
                    'success'     => $terminal_code === '00',
                );

                if ( $terminal_code !== '00' ) {
                    $success = false;
                }
            }
        }

        $this->log( 'info', 'Respuesta de cierre recibida', array(
            'success' => $success,
            'terminals_count' => count( $terminals ),
        ) );

        return array(
            'success'   => $success,
            'terminals' => $terminals,
            'raw_response' => $response,
        );
    }

    /**
     * Test de conexión con la API
     */
    public function test_connection() {
        $result = $this->preregistro();

        if ( is_wp_error( $result ) ) {
            return array(
                'success' => false,
                'message' => $result->get_error_message(),
                'error_code' => $result->get_error_code(),
            );
        }

        return array(
            'success' => true,
            'message' => __( 'Conexión exitosa con Mega Soft', 'woocommerce-megasoft-gateway-v2' ),
            'control' => isset( $result['control'] ) ? $result['control'] : '',
        );
    }

    /**
     * Normalizar respuesta de transacción a formato consistente
     */
    private function normalize_transaction_response( $response ) {
        $codigo = isset( $response['codigo'] ) ? $response['codigo'] : '99';
        $approved = ( $codigo === '00' );

        return array(
            'success'      => $approved,
            'approved'     => $approved,
            'codigo'       => $codigo,
            'descripcion'  => isset( $response['descripcion'] ) ? $response['descripcion'] : '',
            'control'      => isset( $response['control'] ) ? $response['control'] : '',
            'factura'      => isset( $response['factura'] ) ? $response['factura'] : '',
            'authid'       => isset( $response['authid'] ) ? $response['authid'] : '',
            'authname'     => isset( $response['authname'] ) ? $response['authname'] : '',
            'referencia'   => isset( $response['referencia'] ) ? $response['referencia'] : '',
            'tarjeta'      => isset( $response['tarjeta'] ) ? $response['tarjeta'] : '',
            'terminal'     => isset( $response['terminal'] ) ? $response['terminal'] : '',
            'lote'         => isset( $response['lote'] ) ? $response['lote'] : '',
            'seqnum'       => isset( $response['seqnum'] ) ? $response['seqnum'] : '',
            'vtid'         => isset( $response['vtid'] ) ? $response['vtid'] : '',
            'rifbanco'     => isset( $response['rifbanco'] ) ? $response['rifbanco'] : '',
            'afiliacion'   => isset( $response['afiliacion'] ) ? $response['afiliacion'] : '',
            'marca'        => isset( $response['marca'] ) ? $response['marca'] : '',
            'voucher'      => isset( $response['voucher'] ) ? $response['voucher'] : null,
            'monto'        => isset( $response['monto'] ) ? $response['monto'] : '',
            'montoDivisa'  => isset( $response['montoDivisa'] ) ? $response['montoDivisa'] : '',
            'monedaInicio' => isset( $response['monedaInicio'] ) ? $response['monedaInicio'] : '',
            'monedaFin'    => isset( $response['monedaFin'] ) ? $response['monedaFin'] : '',
            'moneda_pago'  => isset( $response['moneda_pago'] ) ? $response['moneda_pago'] : '',
            'raw_response' => $response,
        );
    }

    /**
     * Formatear monto para API (formato: 10+2 sin decimales ni separadores)
     */
    public static function format_amount( $amount ) {
        // Convertir a float y multiplicar por 100
        $amount = floatval( $amount ) * 100;
        // Retornar como entero (sin decimales)
        return number_format( $amount, 0, '', '' );
    }

    /**
     * Formatear monto desde API a formato decimal
     */
    public static function parse_amount( $amount ) {
        // Si viene con decimales, retornar como está
        if ( strpos( $amount, '.' ) !== false || strpos( $amount, ',' ) !== false ) {
            return floatval( str_replace( ',', '.', $amount ) );
        }

        // Si es formato crudo (10+2), dividir por 100
        return floatval( $amount ) / 100;
    }
}
