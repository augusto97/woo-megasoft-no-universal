<?php
/**
 * MegaSoft Diagnostics Class
 * Script de diagnóstico para identificar problemas de conexión
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MegaSoft_Diagnostics {

    private $results = array();
    private $errors = array();
    private $warnings = array();
    private $success_count = 0;
    private $error_count = 0;
    private $warning_count = 0;

    /**
     * Ejecutar diagnóstico completo
     */
    public function run_full_diagnostic() {
        $this->add_section_header( '🔍 DIAGNÓSTICO MEGA SOFT GATEWAY' );

        // 1. Verificar simulador PG Inactivo
        $this->check_pg_simulator();

        // 2. Verificar configuración del gateway
        $this->check_gateway_configuration();

        // 3. Verificar credenciales
        $this->check_credentials();

        // 4. Verificar conectividad
        $this->check_connectivity();

        // 5. Verificar SSL
        $this->check_ssl();

        // 6. Verificar permisos y sistema
        $this->check_system_requirements();

        // 7. Verificar base de datos
        $this->check_database();

        // 8. Probar pre-registro
        $this->test_preregistration();

        // 9. Verificar logs recientes
        $this->check_recent_logs();

        // Resumen final
        $this->generate_summary();

        return array(
            'results' => $this->results,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'success_count' => $this->success_count,
            'error_count' => $this->error_count,
            'warning_count' => $this->warning_count
        );
    }

    /**
     * 1. Verificar simulador PG Inactivo
     */
    private function check_pg_simulator() {
        $this->add_section_header( '1️⃣ Verificación de Simulador PG Inactivo' );

        $is_simulating = get_option( 'megasoft_simulate_pg_inactive' );

        if ( $is_simulating ) {
            $this->add_error(
                'SIMULADOR ACTIVO',
                '⚠️ El simulador de PG inactivo está ACTIVO. Esto está causando que todas las conexiones fallen intencionalmente.',
                'Desactiva el simulador desde: WooCommerce > Ajustes > Pagos > Mega Soft > Desactivar Simulación'
            );
        } else {
            $this->add_success(
                'SIMULADOR INACTIVO',
                '✅ El simulador está desactivado. Las conexiones deberían funcionar normalmente.'
            );
        }
    }

    /**
     * 2. Verificar configuración del gateway
     */
    private function check_gateway_configuration() {
        $this->add_section_header( '2️⃣ Configuración del Gateway' );

        $gateway = new WC_Gateway_MegaSoft_Universal();

        // Verificar si está habilitado
        if ( $gateway->enabled !== 'yes' ) {
            $this->add_error(
                'GATEWAY DESHABILITADO',
                '❌ El gateway está deshabilitado.',
                'Actívalo desde: WooCommerce > Ajustes > Pagos > Mega Soft'
            );
        } else {
            $this->add_success(
                'GATEWAY HABILITADO',
                '✅ El gateway está activo.'
            );
        }

        // Verificar modo
        $test_mode = $gateway->get_option( 'testmode' ) === 'yes';
        if ( $test_mode ) {
            $this->add_warning(
                'MODO PRUEBA',
                '⚠️ El gateway está en MODO DE PRUEBA.',
                'URL: https://paytest.megasoft.com.ve/action/'
            );
        } else {
            $this->add_info(
                'MODO PRODUCCIÓN',
                '🚀 El gateway está en MODO DE PRODUCCIÓN.',
                'URL: https://e-payment.megasoft.com.ve/action/'
            );
        }

        // Verificar debug
        $debug_mode = $gateway->get_option( 'debug' ) === 'yes';
        if ( $debug_mode ) {
            $this->add_info(
                'MODO DEBUG ACTIVO',
                '📝 Los logs detallados están habilitados.'
            );
        } else {
            $this->add_warning(
                'MODO DEBUG INACTIVO',
                '⚠️ Los logs detallados están deshabilitados.',
                'Recomendado activar para diagnóstico.'
            );
        }
    }

    /**
     * 3. Verificar credenciales
     */
    private function check_credentials() {
        $this->add_section_header( '3️⃣ Verificación de Credenciales' );

        $gateway = new WC_Gateway_MegaSoft_Universal();

        $cod_afiliacion = $gateway->get_option( 'cod_afiliacion' );
        $api_user = $gateway->get_option( 'api_user' );
        $api_password = $gateway->get_option( 'api_password' );

        if ( empty( $cod_afiliacion ) ) {
            $this->add_error(
                'CÓDIGO AFILIACIÓN FALTANTE',
                '❌ No se ha configurado el código de afiliación.',
                'Configúralo en: WooCommerce > Ajustes > Pagos > Mega Soft'
            );
        } else {
            $this->add_success(
                'CÓDIGO AFILIACIÓN',
                '✅ Configurado: ' . $cod_afiliacion
            );
        }

        if ( empty( $api_user ) ) {
            $this->add_error(
                'USUARIO API FALTANTE',
                '❌ No se ha configurado el usuario API.',
                'Configúralo en: WooCommerce > Ajustes > Pagos > Mega Soft'
            );
        } else {
            $this->add_success(
                'USUARIO API',
                '✅ Configurado: ' . $api_user
            );
        }

        if ( empty( $api_password ) ) {
            $this->add_error(
                'CONTRASEÑA API FALTANTE',
                '❌ No se ha configurado la contraseña API.',
                'Configúrala en: WooCommerce > Ajustes > Pagos > Mega Soft'
            );
        } else {
            $this->add_success(
                'CONTRASEÑA API',
                '✅ Configurada (longitud: ' . strlen( $api_password ) . ' caracteres)'
            );
        }
    }

    /**
     * 4. Verificar conectividad
     */
    private function check_connectivity() {
        $this->add_section_header( '4️⃣ Pruebas de Conectividad' );

        $gateway = new WC_Gateway_MegaSoft_Universal();
        $test_mode = $gateway->get_option( 'testmode' ) === 'yes';
        $base_url = $test_mode ? 'https://paytest.megasoft.com.ve/' : 'https://e-payment.megasoft.com.ve/';

        // Verificar DNS
        $this->add_info( 'RESOLVIENDO DNS', 'Verificando resolución DNS...' );
        $host = $test_mode ? 'paytest.megasoft.com.ve' : 'e-payment.megasoft.com.ve';
        $ip = gethostbyname( $host );

        if ( $ip === $host ) {
            $this->add_error(
                'ERROR DNS',
                '❌ No se puede resolver el dominio: ' . $host,
                'Verifica tu conexión a internet y DNS del servidor.'
            );
        } else {
            $this->add_success(
                'DNS RESUELTO',
                '✅ Dominio resuelve a: ' . $ip
            );
        }

        // Prueba de conexión HTTP simple
        $this->add_info( 'PROBANDO CONEXIÓN HTTP', 'Conectando a ' . $base_url . '...' );

        $response = wp_remote_get( $base_url, array(
            'timeout' => 15,
            'sslverify' => false,
            'headers' => array(
                'User-Agent' => 'MegaSoft-Diagnostic/1.0'
            )
        ) );

        if ( is_wp_error( $response ) ) {
            $this->add_error(
                'ERROR DE CONEXIÓN HTTP',
                '❌ ' . $response->get_error_message(),
                'Código: ' . $response->get_error_code()
            );
        } else {
            $code = wp_remote_retrieve_response_code( $response );
            if ( $code >= 200 && $code < 500 ) {
                $this->add_success(
                    'CONEXIÓN HTTP',
                    '✅ Servidor responde (HTTP ' . $code . ')'
                );
            } else {
                $this->add_error(
                    'ERROR HTTP',
                    '❌ Servidor responde con error (HTTP ' . $code . ')',
                    'El servidor está inaccesible o no funciona correctamente.'
                );
            }
        }

        // Verificar puerto 443 (HTTPS)
        $this->add_info( 'VERIFICANDO PUERTO 443', 'Comprobando acceso HTTPS...' );
        $socket = @fsockopen( 'ssl://' . $host, 443, $errno, $errstr, 10 );

        if ( $socket ) {
            $this->add_success(
                'PUERTO 443',
                '✅ Puerto HTTPS accesible'
            );
            fclose( $socket );
        } else {
            $this->add_error(
                'PUERTO 443 BLOQUEADO',
                '❌ No se puede conectar al puerto 443',
                'Error: ' . $errstr . ' (Código: ' . $errno . ')'
            );
        }
    }

    /**
     * 5. Verificar SSL
     */
    private function check_ssl() {
        $this->add_section_header( '5️⃣ Verificación SSL' );

        $gateway = new WC_Gateway_MegaSoft_Universal();
        $test_mode = $gateway->get_option( 'testmode' ) === 'yes';

        // Verificar SSL del sitio
        if ( is_ssl() ) {
            $this->add_success(
                'SSL SITIO',
                '✅ Tu sitio usa HTTPS correctamente.'
            );
        } else {
            if ( ! $test_mode ) {
                $this->add_error(
                    'SSL REQUERIDO',
                    '❌ Tu sitio NO usa HTTPS. Esto es OBLIGATORIO en producción.',
                    'Instala un certificado SSL antes de usar el gateway en producción.'
                );
            } else {
                $this->add_warning(
                    'SSL RECOMENDADO',
                    '⚠️ Tu sitio NO usa HTTPS. Recomendado incluso en pruebas.',
                    'Instala un certificado SSL (Let\'s Encrypt es gratuito).'
                );
            }
        }

        // Verificar extensiones SSL
        if ( function_exists( 'openssl_version_text' ) ) {
            $this->add_success(
                'OPENSSL',
                '✅ OpenSSL está disponible: ' . openssl_version_text()
            );
        } else {
            $this->add_error(
                'OPENSSL FALTANTE',
                '❌ OpenSSL no está disponible.',
                'Contacta a tu proveedor de hosting para habilitarlo.'
            );
        }
    }

    /**
     * 6. Verificar sistema
     */
    private function check_system_requirements() {
        $this->add_section_header( '6️⃣ Requisitos del Sistema' );

        // PHP Version
        $php_version = PHP_VERSION;
        if ( version_compare( $php_version, '7.4', '>=' ) ) {
            $this->add_success(
                'VERSIÓN PHP',
                '✅ PHP ' . $php_version . ' (requerido: 7.4+)'
            );
        } else {
            $this->add_error(
                'PHP DESACTUALIZADO',
                '❌ PHP ' . $php_version . ' (requerido: 7.4+)',
                'Actualiza PHP a una versión soportada.'
            );
        }

        // cURL
        if ( function_exists( 'curl_version' ) ) {
            $curl_info = curl_version();
            $this->add_success(
                'CURL',
                '✅ cURL ' . $curl_info['version'] . ' disponible'
            );
        } else {
            $this->add_error(
                'CURL FALTANTE',
                '❌ cURL no está disponible.',
                'Contacta a tu proveedor de hosting para habilitarlo.'
            );
        }

        // WordPress Version
        global $wp_version;
        if ( version_compare( $wp_version, '5.8', '>=' ) ) {
            $this->add_success(
                'WORDPRESS',
                '✅ WordPress ' . $wp_version . ' (requerido: 5.8+)'
            );
        } else {
            $this->add_warning(
                'WORDPRESS',
                '⚠️ WordPress ' . $wp_version . ' (recomendado: 5.8+)'
            );
        }

        // WooCommerce Version
        if ( defined( 'WC_VERSION' ) ) {
            if ( version_compare( WC_VERSION, '6.0', '>=' ) ) {
                $this->add_success(
                    'WOOCOMMERCE',
                    '✅ WooCommerce ' . WC_VERSION . ' (requerido: 6.0+)'
                );
            } else {
                $this->add_warning(
                    'WOOCOMMERCE',
                    '⚠️ WooCommerce ' . WC_VERSION . ' (recomendado: 6.0+)'
                );
            }
        } else {
            $this->add_error(
                'WOOCOMMERCE',
                '❌ WooCommerce no está activo.'
            );
        }
    }

    /**
     * 7. Verificar base de datos
     */
    private function check_database() {
        $this->add_section_header( '7️⃣ Verificación de Base de Datos' );

        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_transactions';

        // Verificar si la tabla existe
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );

        if ( $table_exists ) {
            $this->add_success(
                'TABLA TRANSACCIONES',
                '✅ Tabla existe: ' . $table_name
            );

            // Contar registros
            $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
            $this->add_info(
                'REGISTROS',
                '📊 Total de transacciones: ' . $count
            );

            // Verificar transacciones recientes
            $recent = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table_name} WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $this->add_info(
                'TRANSACCIONES RECIENTES',
                '📊 Últimas 24 horas: ' . $recent
            );

        } else {
            $this->add_error(
                'TABLA NO EXISTE',
                '❌ La tabla de transacciones no existe.',
                'Desactiva y reactiva el plugin para crearla.'
            );
        }
    }

    /**
     * 8. Probar pre-registro
     */
    private function test_preregistration() {
        $this->add_section_header( '8️⃣ Prueba de Pre-Registro' );

        $gateway = new WC_Gateway_MegaSoft_Universal();
        $cod_afiliacion = $gateway->get_option( 'cod_afiliacion' );
        $api_user = $gateway->get_option( 'api_user' );
        $api_password = $gateway->get_option( 'api_password' );
        $test_mode = $gateway->get_option( 'testmode' ) === 'yes';

        if ( empty( $cod_afiliacion ) || empty( $api_user ) || empty( $api_password ) ) {
            $this->add_warning(
                'PRUEBA OMITIDA',
                '⚠️ No se puede probar pre-registro sin credenciales completas.'
            );
            return;
        }

        // Verificar simulador
        if ( get_option( 'megasoft_simulate_pg_inactive' ) ) {
            $this->add_warning(
                'SIMULADOR ACTIVO',
                '⚠️ No se puede probar con el simulador activo.'
            );
            return;
        }

        $this->add_info( 'PROBANDO PRE-REGISTRO', 'Intentando crear un pre-registro de prueba...' );

        $base_url = $test_mode ? 'https://paytest.megasoft.com.ve/action/' : 'https://e-payment.megasoft.com.ve/action/';

        $xml_data = '<request>';
        $xml_data .= '<cod_afiliacion>' . esc_html( $cod_afiliacion ) . '</cod_afiliacion>';
        $xml_data .= '<factura>DIAGNOSTIC_' . time() . '</factura>';
        $xml_data .= '<monto>0.01</monto>';
        $xml_data .= '<nombre>Test Diagnostico</nombre>';
        $xml_data .= '<tipo>V</tipo>';
        $xml_data .= '<cedula_rif>12345678</cedula_rif>';
        $xml_data .= '</request>';

        $auth_credentials = base64_encode( $api_user . ':' . $api_password );
        $headers = array(
            'Authorization' => 'Basic ' . $auth_credentials,
            'Content-Type'  => 'text/xml'
        );

        $response = wp_remote_post( $base_url . 'paymentgatewayuniversal-prereg', array(
            'headers'   => $headers,
            'body'      => $xml_data,
            'timeout'   => 30,
            'sslverify' => false,
        ) );

        if ( is_wp_error( $response ) ) {
            $error_code = $response->get_error_code();
            $error_message = $response->get_error_message();

            $this->add_error(
                'ERROR EN PRE-REGISTRO',
                '❌ ' . $error_message,
                'Código de error: ' . $error_code
            );

            // Diagnóstico específico
            if ( strpos( strtolower( $error_message ), 'could not resolve host' ) !== false ) {
                $this->add_info(
                    'DIAGNÓSTICO',
                    '🔍 El servidor no puede resolver el dominio de Mega Soft. Posibles causas:',
                    '• Problema con el DNS del servidor' . "\n" .
                    '• Firewall bloqueando la conexión' . "\n" .
                    '• Problema temporal con Mega Soft'
                );
            } elseif ( strpos( strtolower( $error_message ), 'connection timed out' ) !== false ) {
                $this->add_info(
                    'DIAGNÓSTICO',
                    '🔍 La conexión expiró. Posibles causas:',
                    '• Servidor de Mega Soft no responde' . "\n" .
                    '• Firewall bloqueando la conexión' . "\n" .
                    '• Timeout muy corto (actual: 30s)'
                );
            }

        } else {
            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );

            if ( $response_code === 200 ) {
                $control_number = trim( $response_body );

                if ( is_numeric( $control_number ) && strlen( $control_number ) >= 10 ) {
                    $this->add_success(
                        'PRE-REGISTRO EXITOSO',
                        '✅ Se obtuvo número de control: ' . $control_number,
                        '¡La conexión con Mega Soft funciona correctamente!'
                    );
                } else {
                    $this->add_error(
                        'RESPUESTA INVÁLIDA',
                        '❌ Respuesta del servidor: ' . substr( $response_body, 0, 200 ),
                        'Posibles causas: credenciales incorrectas, configuración errónea en Mega Soft'
                    );
                }
            } else {
                $this->add_error(
                    'ERROR HTTP',
                    '❌ HTTP ' . $response_code . ': ' . substr( $response_body, 0, 200 ),
                    'El servidor de Mega Soft respondió con error.'
                );
            }
        }
    }

    /**
     * 9. Verificar logs recientes
     */
    private function check_recent_logs() {
        $this->add_section_header( '9️⃣ Logs Recientes' );

        global $wpdb;

        $table_name = $wpdb->prefix . 'megasoft_logs';
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );

        if ( ! $table_exists ) {
            $this->add_warning(
                'LOGS NO DISPONIBLES',
                '⚠️ La tabla de logs no existe todavía.'
            );
            return;
        }

        // Contar errores recientes
        $error_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name}
             WHERE level = 'ERROR'
             AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );

        if ( $error_count > 0 ) {
            $this->add_warning(
                'ERRORES RECIENTES',
                '⚠️ ' . $error_count . ' errores en las últimas 24 horas'
            );

            // Mostrar últimos 5 errores
            $recent_errors = $wpdb->get_results(
                "SELECT message, created_at FROM {$table_name}
                 WHERE level = 'ERROR'
                 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 ORDER BY created_at DESC
                 LIMIT 5",
                ARRAY_A
            );

            foreach ( $recent_errors as $error ) {
                $this->add_info(
                    'ERROR',
                    '• ' . $error['message'],
                    'Fecha: ' . $error['created_at']
                );
            }
        } else {
            $this->add_success(
                'SIN ERRORES',
                '✅ No hay errores recientes en los logs.'
            );
        }
    }

    /**
     * Generar resumen final
     */
    private function generate_summary() {
        $this->add_section_header( '📋 RESUMEN DEL DIAGNÓSTICO' );

        $total_checks = $this->success_count + $this->error_count + $this->warning_count;

        if ( $this->error_count === 0 && $this->warning_count === 0 ) {
            $this->add_success(
                'ESTADO GENERAL',
                '✅ ¡TODO EN ORDEN! El gateway debería funcionar correctamente.',
                'Total de verificaciones: ' . $total_checks
            );
        } elseif ( $this->error_count > 0 ) {
            $this->add_error(
                'PROBLEMAS CRÍTICOS DETECTADOS',
                '❌ Se encontraron ' . $this->error_count . ' problemas críticos que DEBEN resolverse.',
                'Revisa los errores anteriores y corrígelos uno por uno.'
            );
        } else {
            $this->add_warning(
                'ADVERTENCIAS DETECTADAS',
                '⚠️ Se encontraron ' . $this->warning_count . ' advertencias.',
                'El gateway puede funcionar, pero se recomienda revisar las advertencias.'
            );
        }

        $this->add_info(
            'ESTADÍSTICAS',
            '📊 Verificaciones exitosas: ' . $this->success_count . "\n" .
            '⚠️ Advertencias: ' . $this->warning_count . "\n" .
            '❌ Errores críticos: ' . $this->error_count
        );
    }

    /**
     * Agregar encabezado de sección
     */
    private function add_section_header( $title ) {
        $this->results[] = array(
            'type' => 'header',
            'title' => $title
        );
    }

    /**
     * Agregar resultado exitoso
     */
    private function add_success( $title, $message, $details = '' ) {
        $this->results[] = array(
            'type' => 'success',
            'title' => $title,
            'message' => $message,
            'details' => $details
        );
        $this->success_count++;
    }

    /**
     * Agregar error
     */
    private function add_error( $title, $message, $details = '' ) {
        $this->results[] = array(
            'type' => 'error',
            'title' => $title,
            'message' => $message,
            'details' => $details
        );
        $this->errors[] = array(
            'title' => $title,
            'message' => $message,
            'details' => $details
        );
        $this->error_count++;
    }

    /**
     * Agregar advertencia
     */
    private function add_warning( $title, $message, $details = '' ) {
        $this->results[] = array(
            'type' => 'warning',
            'title' => $title,
            'message' => $message,
            'details' => $details
        );
        $this->warnings[] = array(
            'title' => $title,
            'message' => $message,
            'details' => $details
        );
        $this->warning_count++;
    }

    /**
     * Agregar información
     */
    private function add_info( $title, $message, $details = '' ) {
        $this->results[] = array(
            'type' => 'info',
            'title' => $title,
            'message' => $message,
            'details' => $details
        );
    }
}
