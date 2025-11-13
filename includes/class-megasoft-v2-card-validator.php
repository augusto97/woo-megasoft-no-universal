<?php
/**
 * MegaSoft Gateway v2 - Card Validator (Server-Side)
 *
 * Server-side card validation including BIN detection,
 * brand identification, and Venezuelan card validation
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MegaSoft_V2_Card_Validator {

    /**
     * Card brand patterns with BIN ranges
     */
    private static $card_brands = array(
        'visa' => array(
            'pattern' => '/^4/',
            'lengths' => array( 13, 16, 19 ),
            'cvv_length' => 3,
            'name' => 'Visa',
        ),
        'mastercard' => array(
            'pattern' => '/^(5[1-5]|2[2-7])/',
            'lengths' => array( 16 ),
            'cvv_length' => 3,
            'name' => 'MasterCard',
        ),
        'amex' => array(
            'pattern' => '/^3[47]/',
            'lengths' => array( 15 ),
            'cvv_length' => 4,
            'name' => 'American Express',
        ),
        'discover' => array(
            'pattern' => '/^6(?:011|5)/',
            'lengths' => array( 16 ),
            'cvv_length' => 3,
            'name' => 'Discover',
        ),
        'diners' => array(
            'pattern' => '/^3(?:0[0-5]|[68])/',
            'lengths' => array( 14 ),
            'cvv_length' => 3,
            'name' => 'Diners Club',
        ),
        'jcb' => array(
            'pattern' => '/^35/',
            'lengths' => array( 16 ),
            'cvv_length' => 3,
            'name' => 'JCB',
        ),
        'maestro' => array(
            'pattern' => '/^(5018|5020|5038|5893|6304|6759|6761|6762|6763)/',
            'lengths' => array( 12, 13, 14, 15, 16, 17, 18, 19 ),
            'cvv_length' => 3,
            'name' => 'Maestro',
        ),
    );

    /**
     * Venezuelan banks BIN ranges (first 6 digits)
     * This is a sample - should be updated with actual Venezuelan BINs
     */
    private static $venezuelan_bins = array(
        // Banco de Venezuela
        '450962', '450963', '450964',
        // Banesco
        '413740', '413741', '522157',
        // Mercantil
        '533310', '533311', '404057',
        // Provincial
        '450906', '450907', '450908',
        // Banco Bicentenario
        '414076', '414077',
        // BOD
        '404006', '404007',
        // Bancaribe
        '420157', '420158',
        // Add more Venezuelan BINs as needed
    );

    /**
     * Test card numbers (for test mode only)
     */
    private static $test_cards = array(
        '4111111111111111', // Visa test card
        '5555555555554444', // MasterCard test card
        '378282246310005',  // Amex test card
        '6011111111111117', // Discover test card
    );

    /**
     * Detect card brand from number
     *
     * @param string $card_number Card number
     * @return array|null Array with 'type' and 'name', or null if unknown
     */
    public static function detect_brand( $card_number ) {
        $card_number = self::sanitize( $card_number );

        foreach ( self::$card_brands as $type => $brand ) {
            if ( preg_match( $brand['pattern'], $card_number ) ) {
                return array(
                    'type' => $type,
                    'name' => $brand['name'],
                );
            }
        }

        return null;
    }

    /**
     * Validate card number
     *
     * @param string $card_number Card number
     * @param array $allowed_brands Array of allowed brand types (optional)
     * @return array Validation result
     */
    public static function validate( $card_number, $allowed_brands = array() ) {
        $card_number = self::sanitize( $card_number );

        // Check if empty
        if ( empty( $card_number ) ) {
            return array(
                'valid' => false,
                'message' => __( 'Número de tarjeta es requerido', 'woocommerce-megasoft-gateway-v2' ),
            );
        }

        // Detect brand
        $brand = self::detect_brand( $card_number );

        if ( ! $brand ) {
            return array(
                'valid' => false,
                'message' => __( 'Marca de tarjeta no reconocida', 'woocommerce-megasoft-gateway-v2' ),
            );
        }

        $brand_info = self::$card_brands[ $brand['type'] ];

        // Check if brand is allowed
        if ( ! empty( $allowed_brands ) && ! in_array( $brand['type'], $allowed_brands, true ) ) {
            return array(
                'valid' => false,
                'message' => sprintf(
                    __( 'Tarjetas %s no son aceptadas', 'woocommerce-megasoft-gateway-v2' ),
                    $brand['name']
                ),
            );
        }

        // Check length
        if ( ! in_array( strlen( $card_number ), $brand_info['lengths'], true ) ) {
            return array(
                'valid' => false,
                'message' => __( 'Longitud de tarjeta inválida', 'woocommerce-megasoft-gateway-v2' ),
            );
        }

        // Validate Luhn
        if ( ! self::validate_luhn( $card_number ) ) {
            return array(
                'valid' => false,
                'message' => __( 'Número de tarjeta inválido', 'woocommerce-megasoft-gateway-v2' ),
            );
        }

        return array(
            'valid' => true,
            'brand' => $brand,
            'message' => __( 'Tarjeta válida', 'woocommerce-megasoft-gateway-v2' ),
        );
    }

    /**
     * Validate using Luhn algorithm
     *
     * @param string $card_number Card number
     * @return bool True if valid
     */
    public static function validate_luhn( $card_number ) {
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
     * Check if card is Venezuelan
     *
     * @param string $card_number Card number
     * @return bool True if Venezuelan card detected
     */
    public static function is_venezuelan_card( $card_number ) {
        $card_number = self::sanitize( $card_number );

        if ( strlen( $card_number ) < 6 ) {
            return false;
        }

        $bin = substr( $card_number, 0, 6 );

        return in_array( $bin, self::$venezuelan_bins, true );
    }

    /**
     * Check if card is a test card
     *
     * @param string $card_number Card number
     * @return bool True if test card
     */
    public static function is_test_card( $card_number ) {
        $card_number = self::sanitize( $card_number );
        return in_array( $card_number, self::$test_cards, true );
    }

    /**
     * Validate CVV
     *
     * @param string $cvv CVV
     * @param string $card_brand Card brand type (optional)
     * @return array Validation result
     */
    public static function validate_cvv( $cvv, $card_brand = null ) {
        // Remove spaces
        $cvv = trim( $cvv );

        if ( empty( $cvv ) ) {
            return array(
                'valid' => false,
                'message' => __( 'CVV es requerido', 'woocommerce-megasoft-gateway-v2' ),
            );
        }

        // Check if only digits
        if ( ! preg_match( '/^[0-9]+$/', $cvv ) ) {
            return array(
                'valid' => false,
                'message' => __( 'CVV debe contener solo dígitos', 'woocommerce-megasoft-gateway-v2' ),
            );
        }

        // Determine expected length
        $expected_length = 3; // Default for most cards

        if ( $card_brand && isset( self::$card_brands[ $card_brand ] ) ) {
            $expected_length = self::$card_brands[ $card_brand ]['cvv_length'];
        }

        if ( strlen( $cvv ) !== $expected_length ) {
            return array(
                'valid' => false,
                'message' => sprintf(
                    __( 'CVV debe tener %d dígitos', 'woocommerce-megasoft-gateway-v2' ),
                    $expected_length
                ),
            );
        }

        return array(
            'valid' => true,
            'message' => __( 'CVV válido', 'woocommerce-megasoft-gateway-v2' ),
        );
    }

    /**
     * Validate expiry date
     *
     * @param string $month Month (MM)
     * @param string $year Year (YY or YYYY)
     * @return array Validation result
     */
    public static function validate_expiry( $month, $year ) {
        $month = intval( $month );
        $year = intval( $year );

        // Validate month
        if ( $month < 1 || $month > 12 ) {
            return array(
                'valid' => false,
                'message' => __( 'Mes inválido', 'woocommerce-megasoft-gateway-v2' ),
            );
        }

        // Convert 2-digit year to 4-digit
        if ( $year < 100 ) {
            $year += 2000;
        }

        // Check if expired
        $current_year = (int) date( 'Y' );
        $current_month = (int) date( 'm' );

        if ( $year < $current_year ) {
            return array(
                'valid' => false,
                'message' => __( 'Tarjeta expirada', 'woocommerce-megasoft-gateway-v2' ),
            );
        }

        if ( $year === $current_year && $month < $current_month ) {
            return array(
                'valid' => false,
                'message' => __( 'Tarjeta expirada', 'woocommerce-megasoft-gateway-v2' ),
            );
        }

        // Check if too far in future (more than 20 years)
        if ( $year > $current_year + 20 ) {
            return array(
                'valid' => false,
                'message' => __( 'Año de expiración inválido', 'woocommerce-megasoft-gateway-v2' ),
            );
        }

        return array(
            'valid' => true,
            'message' => __( 'Fecha de expiración válida', 'woocommerce-megasoft-gateway-v2' ),
        );
    }

    /**
     * Get BIN (Bank Identification Number) from card
     *
     * @param string $card_number Card number
     * @return string BIN (first 6 digits)
     */
    public static function get_bin( $card_number ) {
        $card_number = self::sanitize( $card_number );
        return substr( $card_number, 0, 6 );
    }

    /**
     * Get last 4 digits of card
     *
     * @param string $card_number Card number
     * @return string Last 4 digits
     */
    public static function get_last_four( $card_number ) {
        $card_number = self::sanitize( $card_number );
        return substr( $card_number, -4 );
    }

    /**
     * Mask card number (show only last 4)
     *
     * @param string $card_number Card number
     * @return string Masked card number
     */
    public static function mask_card_number( $card_number ) {
        $card_number = self::sanitize( $card_number );
        $last_four = self::get_last_four( $card_number );
        return str_repeat( '*', strlen( $card_number ) - 4 ) . $last_four;
    }

    /**
     * Format card number with spaces
     *
     * @param string $card_number Card number
     * @return string Formatted card number
     */
    public static function format_card_number( $card_number ) {
        $card_number = self::sanitize( $card_number );

        // Detect brand for proper formatting
        $brand = self::detect_brand( $card_number );

        // Amex uses 4-6-5 format
        if ( $brand && $brand['type'] === 'amex' ) {
            return substr( $card_number, 0, 4 ) . ' ' .
                   substr( $card_number, 4, 6 ) . ' ' .
                   substr( $card_number, 10 );
        }

        // Most cards use 4-4-4-4 format
        return implode( ' ', str_split( $card_number, 4 ) );
    }

    /**
     * Sanitize card number (remove all non-digits)
     *
     * @param string $card_number Card number
     * @return string Sanitized card number
     */
    public static function sanitize( $card_number ) {
        return preg_replace( '/\D/', '', $card_number );
    }

    /**
     * Check if installments are allowed for card type
     *
     * @param string $card_brand Card brand
     * @param string $card_type Type (CREDITO or DEBITO)
     * @return bool True if installments allowed
     */
    public static function allows_installments( $card_brand, $card_type ) {
        // Only credit cards can have installments
        if ( $card_type !== 'CREDITO' ) {
            return false;
        }

        // Most brands allow installments except American Express
        $no_installments = array( 'amex', 'diners' );

        return ! in_array( $card_brand, $no_installments, true );
    }

    /**
     * Get maximum installments for card
     *
     * @param string $card_brand Card brand
     * @param float $amount Transaction amount
     * @return int Maximum installments
     */
    public static function get_max_installments( $card_brand, $amount ) {
        // Default max installments
        $max = 12;

        // Amount-based restrictions
        if ( $amount < 1000 ) {
            $max = 3;
        } elseif ( $amount < 5000 ) {
            $max = 6;
        }

        // Brand-specific restrictions
        if ( $card_brand === 'discover' ) {
            $max = min( $max, 6 );
        }

        return $max;
    }

    /**
     * Validate card data comprehensively
     *
     * @param array $card_data Card data array
     * @return array Validation result
     */
    public static function validate_card_data( $card_data ) {
        $errors = array();

        // Validate card number
        if ( empty( $card_data['number'] ) ) {
            $errors[] = __( 'Número de tarjeta es requerido', 'woocommerce-megasoft-gateway-v2' );
        } else {
            $validation = self::validate( $card_data['number'], $card_data['allowed_brands'] ?? array() );
            if ( ! $validation['valid'] ) {
                $errors[] = $validation['message'];
            } else {
                $brand = $validation['brand']['type'];
                $card_data['detected_brand'] = $brand;
            }
        }

        // Validate CVV
        if ( empty( $card_data['cvv'] ) ) {
            $errors[] = __( 'CVV es requerido', 'woocommerce-megasoft-gateway-v2' );
        } else {
            $cvv_validation = self::validate_cvv(
                $card_data['cvv'],
                $card_data['detected_brand'] ?? null
            );
            if ( ! $cvv_validation['valid'] ) {
                $errors[] = $cvv_validation['message'];
            }
        }

        // Validate expiry
        if ( empty( $card_data['exp_month'] ) || empty( $card_data['exp_year'] ) ) {
            $errors[] = __( 'Fecha de expiración es requerida', 'woocommerce-megasoft-gateway-v2' );
        } else {
            $expiry_validation = self::validate_expiry(
                $card_data['exp_month'],
                $card_data['exp_year']
            );
            if ( ! $expiry_validation['valid'] ) {
                $errors[] = $expiry_validation['message'];
            }
        }

        // Validate cardholder name
        if ( empty( $card_data['name'] ) ) {
            $errors[] = __( 'Nombre del titular es requerido', 'woocommerce-megasoft-gateway-v2' );
        }

        if ( ! empty( $errors ) ) {
            return array(
                'valid' => false,
                'errors' => $errors,
                'message' => implode( '. ', $errors ),
            );
        }

        return array(
            'valid' => true,
            'message' => __( 'Datos de tarjeta válidos', 'woocommerce-megasoft-gateway-v2' ),
            'brand' => $card_data['detected_brand'] ?? null,
        );
    }

    /**
     * Validate CID (Customer Identification) format
     * Format: [V|E|J|G|P|C]12345678
     *
     * @param string $cid Customer identification
     * @return array Validation result
     */
    public static function validate_cid( $cid ) {
        $cid = strtoupper( trim( $cid ) );

        if ( empty( $cid ) ) {
            return array(
                'valid' => false,
                'message' => __( 'Identificación es requerida', 'woocommerce-megasoft-gateway-v2' ),
            );
        }

        // Valid prefixes according to Mega Soft documentation
        // V = Venezolano, E = Extranjero, J = Jurídico, G = Gubernamental, P = Pasaporte, C = Nuevo tipo
        if ( ! preg_match( '/^[VEJGPC][0-9]{4,12}$/', $cid ) ) {
            return array(
                'valid' => false,
                'message' => __( 'Formato de identificación inválido. Debe comenzar con V, E, J, G, P o C seguido de números (ej: V12345678)', 'woocommerce-megasoft-gateway-v2' ),
            );
        }

        return array(
            'valid' => true,
            'message' => __( 'Identificación válida', 'woocommerce-megasoft-gateway-v2' ),
            'type' => substr( $cid, 0, 1 ),
            'number' => substr( $cid, 1 ),
        );
    }

    /**
     * Format CID to uppercase
     *
     * @param string $cid Customer identification
     * @return string Formatted CID
     */
    public static function format_cid( $cid ) {
        return strtoupper( trim( $cid ) );
    }

    /**
     * Get card info for display (safe for PCI)
     *
     * @param string $card_number Card number
     * @return array Card info
     */
    public static function get_card_info( $card_number ) {
        $card_number = self::sanitize( $card_number );
        $brand = self::detect_brand( $card_number );

        return array(
            'last_four' => self::get_last_four( $card_number ),
            'masked' => self::mask_card_number( $card_number ),
            'brand' => $brand ? $brand['name'] : 'Unknown',
            'brand_type' => $brand ? $brand['type'] : null,
            'is_venezuelan' => self::is_venezuelan_card( $card_number ),
            'bin' => self::get_bin( $card_number ),
        );
    }
}
