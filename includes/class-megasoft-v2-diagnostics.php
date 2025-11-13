<?php
/**
 * MegaSoft Gateway v2 - Diagnostics & Connection Tester
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MegaSoft_V2_Diagnostics {

    /**
     * Test connection to Mega Soft API
     *
     * @param string $api_user Usuario API
     * @param string $api_password Contraseña API
     * @param string $cod_afiliacion Código de afiliación
     * @param bool $testmode Modo de prueba
     * @return array Result
     */
    public static function test_connection( $api_user, $api_password, $cod_afiliacion, $testmode = true ) {
        $results = array(
            'success' => true,
            'tests'   => array(),
        );

        // Test 1: Credentials not empty
        $results['tests']['credentials'] = self::test_credentials( $api_user, $api_password, $cod_afiliacion );
        if ( ! $results['tests']['credentials']['passed'] ) {
            $results['success'] = false;
        }

        // Test 2: SSL Check
        $results['tests']['ssl'] = self::test_ssl();
        if ( ! $results['tests']['ssl']['passed'] ) {
            $results['success'] = false;
        }

        // Test 3: PHP Extensions
        $results['tests']['extensions'] = self::test_php_extensions();
        if ( ! $results['tests']['extensions']['passed'] ) {
            $results['success'] = false;
        }

        // Test 4: Database Tables
        $results['tests']['database'] = self::test_database_tables();
        if ( ! $results['tests']['database']['passed'] ) {
            $results['success'] = false;
        }

        // Test 5: API Connection (PreRegistro)
        if ( ! empty( $api_user ) && ! empty( $api_password ) && ! empty( $cod_afiliacion ) ) {
            $results['tests']['api_connection'] = self::test_api_connection( $api_user, $api_password, $cod_afiliacion, $testmode );
            if ( ! $results['tests']['api_connection']['passed'] ) {
                $results['success'] = false;
            }
        }

        return $results;
    }

    /**
     * Test credentials
     */
    private static function test_credentials( $api_user, $api_password, $cod_afiliacion ) {
        $missing = array();

        if ( empty( $api_user ) ) {
            $missing[] = 'Usuario API';
        }
        if ( empty( $api_password ) ) {
            $missing[] = 'Contraseña API';
        }
        if ( empty( $cod_afiliacion ) ) {
            $missing[] = 'Código de Afiliación';
        }

        if ( ! empty( $missing ) ) {
            return array(
                'passed'  => false,
                'message' => 'Faltan credenciales: ' . implode( ', ', $missing ),
            );
        }

        return array(
            'passed'  => true,
            'message' => 'Todas las credenciales están configuradas',
        );
    }

    /**
     * Test SSL
     */
    private static function test_ssl() {
        if ( ! is_ssl() ) {
            return array(
                'passed'  => false,
                'message' => 'SSL/HTTPS no está activo. Requerido para producción.',
                'warning' => true,
            );
        }

        return array(
            'passed'  => true,
            'message' => 'SSL/HTTPS activo',
        );
    }

    /**
     * Test PHP extensions
     */
    private static function test_php_extensions() {
        $required = array( 'curl', 'json', 'openssl', 'xml', 'simplexml' );
        $missing = array();

        foreach ( $required as $ext ) {
            if ( ! extension_loaded( $ext ) ) {
                $missing[] = $ext;
            }
        }

        if ( ! empty( $missing ) ) {
            return array(
                'passed'  => false,
                'message' => 'Extensiones PHP faltantes: ' . implode( ', ', $missing ),
            );
        }

        return array(
            'passed'  => true,
            'message' => 'Todas las extensiones PHP requeridas están instaladas',
        );
    }

    /**
     * Test database tables
     */
    private static function test_database_tables() {
        global $wpdb;

        $required_tables = array(
            'megasoft_v2_transactions',
            'megasoft_v2_logs',
            'megasoft_v2_failed_webhooks',
            'megasoft_v2_security_log',
        );

        $missing = array();

        foreach ( $required_tables as $table ) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );
            if ( ! $exists ) {
                $missing[] = $table;
            }
        }

        if ( ! empty( $missing ) ) {
            return array(
                'passed'  => false,
                'message' => 'Tablas faltantes: ' . implode( ', ', $missing ) . '. Desactiva y reactiva el plugin.',
            );
        }

        return array(
            'passed'  => true,
            'message' => 'Todas las tablas de base de datos existen',
        );
    }

    /**
     * Test API connection
     */
    private static function test_api_connection( $api_user, $api_password, $cod_afiliacion, $testmode ) {
        $base_url = $testmode ? 'https://paytest.megasoft.com.ve/payment/action/' : 'https://e-payment.megasoft.com.ve/payment/action/';
        $url = $base_url . 'v2-preregistro';

        // Build XML request - PreRegistro solo acepta cod_afiliacion
        $xml = new SimpleXMLElement( '<request/>' );
        $xml->addChild( 'cod_afiliacion', $cod_afiliacion );

        // Build Basic Auth header
        $auth = base64_encode( $api_user . ':' . $api_password );

        // Make request
        $response = wp_remote_post( $url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . $auth,
                'Content-Type'  => 'text/xml',
            ),
            'body'    => $xml->asXML(),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            return array(
                'passed'  => false,
                'message' => 'Error de conexión: ' . $response->get_error_message(),
                'details' => array(
                    'url' => $url,
                    'error' => $response->get_error_message(),
                ),
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $status_code === 401 ) {
            return array(
                'passed'  => false,
                'message' => 'Credenciales inválidas (Error 401). Verifica Usuario y Contraseña.',
                'details' => array(
                    'url' => $url,
                    'status' => $status_code,
                ),
            );
        }

        if ( $status_code !== 200 ) {
            return array(
                'passed'  => false,
                'message' => 'Error HTTP ' . $status_code,
                'details' => array(
                    'url' => $url,
                    'status' => $status_code,
                    'body' => substr( $body, 0, 500 ),
                ),
            );
        }

        // Parse XML response
        libxml_use_internal_errors( true );
        $xml_response = simplexml_load_string( $body );

        if ( $xml_response === false ) {
            return array(
                'passed'  => false,
                'message' => 'Respuesta XML inválida del servidor',
                'details' => array(
                    'body' => substr( $body, 0, 500 ),
                ),
            );
        }

        // Check if we got a control number
        if ( isset( $xml_response->control ) && ! empty( (string) $xml_response->control ) ) {
            return array(
                'passed'  => true,
                'message' => '✓ Conexión exitosa con Mega Soft',
                'details' => array(
                    'url' => $url,
                    'control' => (string) $xml_response->control,
                    'status' => 'Conectado correctamente',
                ),
            );
        }

        // Check for error in response
        if ( isset( $xml_response->codigo ) && (string) $xml_response->codigo !== '00' ) {
            return array(
                'passed'  => false,
                'message' => 'Error de Mega Soft: ' . (string) ( $xml_response->descripcion ?? 'Error desconocido' ),
                'details' => array(
                    'codigo' => (string) $xml_response->codigo,
                    'descripcion' => (string) ( $xml_response->descripcion ?? '' ),
                ),
            );
        }

        return array(
            'passed'  => false,
            'message' => 'Respuesta inesperada del servidor',
            'details' => array(
                'body' => substr( $body, 0, 500 ),
            ),
        );
    }

    /**
     * Get system info
     */
    public static function get_system_info() {
        global $wpdb;

        return array(
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo( 'version' ),
            'woocommerce_version' => defined( 'WC_VERSION' ) ? WC_VERSION : 'N/A',
            'plugin_version' => MEGASOFT_V2_VERSION,
            'ssl_active' => is_ssl(),
            'db_version' => get_option( 'megasoft_v2_db_version', 'N/A' ),
            'webhook_url' => MegaSoft_V2_Webhook::get_webhook_url(),
            'php_extensions' => array(
                'curl' => extension_loaded( 'curl' ),
                'json' => extension_loaded( 'json' ),
                'openssl' => extension_loaded( 'openssl' ),
                'xml' => extension_loaded( 'xml' ),
                'simplexml' => extension_loaded( 'simplexml' ),
            ),
        );
    }

    /**
     * Get logs count
     */
    public static function get_logs_count() {
        global $wpdb;

        $table = $wpdb->prefix . 'megasoft_v2_logs';

        $counts = $wpdb->get_results(
            "SELECT level, COUNT(*) as count FROM $table GROUP BY level",
            ARRAY_A
        );

        $result = array(
            'total' => 0,
            'by_level' => array(),
        );

        foreach ( $counts as $row ) {
            $result['by_level'][ $row['level'] ] = intval( $row['count'] );
            $result['total'] += intval( $row['count'] );
        }

        return $result;
    }

    /**
     * Clear logs older than X days
     */
    public static function clear_old_logs( $days = 30 ) {
        global $wpdb;

        $table = $wpdb->prefix . 'megasoft_v2_logs';
        $date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM $table WHERE created_at < %s",
            $date
        ) );

        return array(
            'success' => true,
            'deleted' => $deleted,
            'message' => sprintf( 'Se eliminaron %d logs antiguos', $deleted ),
        );
    }

    /**
     * Test specific endpoint
     */
    public static function test_endpoint( $endpoint, $api_user, $api_password, $cod_afiliacion, $testmode ) {
        $base_url = $testmode ? 'https://paytest.megasoft.com.ve/payment/action/' : 'https://e-payment.megasoft.com.ve/payment/action/';
        $url = $base_url . $endpoint;

        $auth = base64_encode( $api_user . ':' . $api_password );

        $response = wp_remote_get( $url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . $auth,
            ),
            'timeout' => 10,
        ) );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }

        return array(
            'success' => true,
            'status'  => wp_remote_retrieve_response_code( $response ),
            'body'    => wp_remote_retrieve_body( $response ),
        );
    }
}
