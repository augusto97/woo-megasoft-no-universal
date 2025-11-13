<?php
/**
 * MegaSoft Gateway v2 - Database Installer
 *
 * Maneja la creación y actualización de tablas de base de datos
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MegaSoft_V2_Installer {

    /**
     * Install/Update database tables
     */
    public static function install() {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $charset_collate = $wpdb->get_charset_collate();
        $installed = array();

        // Tabla de transacciones
        $table_transactions = $wpdb->prefix . 'megasoft_v2_transactions';
        $sql_transactions = "CREATE TABLE $table_transactions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            control_number varchar(19) NOT NULL,
            authorization_code varchar(50) DEFAULT NULL,
            transaction_type varchar(50) DEFAULT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(10) DEFAULT 'VES',
            card_last_four varchar(4) DEFAULT NULL,
            card_type varchar(20) DEFAULT NULL,
            response_code varchar(10) DEFAULT NULL,
            response_message text DEFAULT NULL,
            transaction_date datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            raw_response longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY control_number (control_number),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        dbDelta( $sql_transactions );
        $installed[] = 'transactions';

        // Tabla de logs
        $table_logs = $wpdb->prefix . 'megasoft_v2_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED DEFAULT NULL,
            control_number varchar(19) DEFAULT NULL,
            level varchar(10) NOT NULL,
            message text NOT NULL,
            context longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY order_id (order_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        dbDelta( $sql_logs );
        $installed[] = 'logs';

        // Verificar que las tablas se crearon
        $verification = self::verify_tables();

        // Guardar versión
        update_option( 'megasoft_v2_db_version', MEGASOFT_V2_VERSION );
        update_option( 'megasoft_v2_tables_created', current_time( 'mysql' ) );

        return array(
            'installed' => $installed,
            'verification' => $verification,
        );
    }

    /**
     * Verify that all tables exist
     */
    public static function verify_tables() {
        global $wpdb;

        $required_tables = array(
            'megasoft_v2_transactions',
            'megasoft_v2_logs',
        );

        $results = array();

        foreach ( $required_tables as $table ) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

            $results[ $table ] = array(
                'exists' => $exists,
                'table_name' => $table_name,
            );

            // Si existe, obtener conteo
            if ( $exists ) {
                $results[ $table ]['count'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
            }
        }

        return $results;
    }

    /**
     * Force recreate tables (DANGEROUS - only for debugging)
     */
    public static function force_recreate() {
        global $wpdb;

        // DROP tables
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}megasoft_v2_transactions" );
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}megasoft_v2_logs" );

        // Recreate
        return self::install();
    }

    /**
     * Get database status for diagnostics
     */
    public static function get_status() {
        global $wpdb;

        $verification = self::verify_tables();

        return array(
            'db_version' => get_option( 'megasoft_v2_db_version', 'N/A' ),
            'tables_created' => get_option( 'megasoft_v2_tables_created', 'N/A' ),
            'tables' => $verification,
            'wpdb_prefix' => $wpdb->prefix,
        );
    }
}
