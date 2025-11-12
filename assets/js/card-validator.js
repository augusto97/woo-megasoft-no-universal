/**
 * MegaSoft v2 - Card Validator Library
 *
 * Provides card validation utilities including:
 * - Luhn algorithm validation
 * - Card brand detection
 * - Expiry date validation
 * - CVV validation
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 */

(function(window) {
    'use strict';

    var MegaSoftCardValidator = {

        /**
         * Card brand patterns and rules
         */
        cardBrands: {
            visa: {
                pattern: /^4/,
                lengths: [13, 16, 19],
                cvvLength: 3,
                format: /(\d{1,4})/g,
                name: 'Visa'
            },
            mastercard: {
                pattern: /^(5[1-5]|2[2-7])/,
                lengths: [16],
                cvvLength: 3,
                format: /(\d{1,4})/g,
                name: 'MasterCard'
            },
            amex: {
                pattern: /^3[47]/,
                lengths: [15],
                cvvLength: 4,
                format: /(\d{1,4})(\d{1,6})?(\d{1,5})?/,
                name: 'American Express'
            },
            discover: {
                pattern: /^6(?:011|5)/,
                lengths: [16],
                cvvLength: 3,
                format: /(\d{1,4})/g,
                name: 'Discover'
            },
            diners: {
                pattern: /^3(?:0[0-5]|[68])/,
                lengths: [14],
                cvvLength: 3,
                format: /(\d{1,4})/g,
                name: 'Diners Club'
            }
        },

        /**
         * Validate card number using Luhn algorithm
         *
         * @param {string} cardNumber - Card number to validate
         * @return {boolean} True if valid
         */
        validateLuhn: function(cardNumber) {
            // Remove spaces and non-digits
            cardNumber = this.sanitizeCardNumber(cardNumber);

            if (!cardNumber || cardNumber.length < 13) {
                return false;
            }

            var sum = 0;
            var numDigits = cardNumber.length;
            var parity = numDigits % 2;

            for (var i = 0; i < numDigits; i++) {
                var digit = parseInt(cardNumber.charAt(i));

                if (i % 2 === parity) {
                    digit *= 2;
                }

                if (digit > 9) {
                    digit -= 9;
                }

                sum += digit;
            }

            return (sum % 10) === 0;
        },

        /**
         * Detect card brand from card number
         *
         * @param {string} cardNumber - Card number
         * @return {object|null} Card brand object or null
         */
        detectCardBrand: function(cardNumber) {
            cardNumber = this.sanitizeCardNumber(cardNumber);

            for (var brand in this.cardBrands) {
                if (this.cardBrands[brand].pattern.test(cardNumber)) {
                    return {
                        type: brand,
                        name: this.cardBrands[brand].name
                    };
                }
            }

            return null;
        },

        /**
         * Validate card number (Luhn + length)
         *
         * @param {string} cardNumber - Card number
         * @return {object} Validation result
         */
        validateCardNumber: function(cardNumber) {
            cardNumber = this.sanitizeCardNumber(cardNumber);

            var result = {
                valid: false,
                brand: null,
                message: ''
            };

            if (!cardNumber) {
                result.message = 'Card number is required';
                return result;
            }

            var brand = this.detectCardBrand(cardNumber);
            result.brand = brand;

            if (!brand) {
                result.message = 'Unknown card brand';
                return result;
            }

            // Check length
            var brandInfo = this.cardBrands[brand.type];
            if (brandInfo.lengths.indexOf(cardNumber.length) === -1) {
                result.message = 'Invalid card number length for ' + brand.name;
                return result;
            }

            // Validate Luhn
            if (!this.validateLuhn(cardNumber)) {
                result.message = 'Invalid card number';
                return result;
            }

            result.valid = true;
            result.message = 'Valid';
            return result;
        },

        /**
         * Validate expiry date
         *
         * @param {string} month - Month (MM or M)
         * @param {string} year - Year (YY or YYYY)
         * @return {object} Validation result
         */
        validateExpiry: function(month, year) {
            var result = {
                valid: false,
                message: ''
            };

            month = parseInt(month, 10);
            year = parseInt(year, 10);

            if (isNaN(month) || isNaN(year)) {
                result.message = 'Invalid date format';
                return result;
            }

            // Validate month
            if (month < 1 || month > 12) {
                result.message = 'Invalid month';
                return result;
            }

            // Convert 2-digit year to 4-digit
            if (year < 100) {
                year += 2000;
            }

            // Check if expired
            var now = new Date();
            var currentYear = now.getFullYear();
            var currentMonth = now.getMonth() + 1;

            if (year < currentYear || (year === currentYear && month < currentMonth)) {
                result.message = 'Card has expired';
                return result;
            }

            // Check if too far in the future (more than 20 years)
            if (year > currentYear + 20) {
                result.message = 'Invalid expiry year';
                return result;
            }

            result.valid = true;
            result.message = 'Valid';
            return result;
        },

        /**
         * Parse expiry string (MM/YY or MM/YYYY or MMYY)
         *
         * @param {string} expiry - Expiry string
         * @return {object} Month and year
         */
        parseExpiry: function(expiry) {
            expiry = expiry.replace(/\s+/g, '');

            var parts = expiry.split('/');
            var month, year;

            if (parts.length === 2) {
                month = parts[0];
                year = parts[1];
            } else if (expiry.length === 4) {
                month = expiry.substring(0, 2);
                year = expiry.substring(2);
            } else {
                return null;
            }

            return {
                month: month,
                year: year
            };
        },

        /**
         * Validate CVV
         *
         * @param {string} cvv - CVV
         * @param {string} cardBrand - Card brand (optional)
         * @return {object} Validation result
         */
        validateCVV: function(cvv, cardBrand) {
            var result = {
                valid: false,
                message: ''
            };

            cvv = cvv.replace(/\s+/g, '');

            if (!/^\d+$/.test(cvv)) {
                result.message = 'CVV must contain only digits';
                return result;
            }

            var expectedLength = 3;

            if (cardBrand && this.cardBrands[cardBrand]) {
                expectedLength = this.cardBrands[cardBrand].cvvLength;
            }

            if (cvv.length !== expectedLength) {
                result.message = 'CVV must be ' + expectedLength + ' digits';
                return result;
            }

            result.valid = true;
            result.message = 'Valid';
            return result;
        },

        /**
         * Format card number with spaces
         *
         * @param {string} cardNumber - Card number
         * @param {string} cardBrand - Card brand (optional)
         * @return {string} Formatted card number
         */
        formatCardNumber: function(cardNumber, cardBrand) {
            cardNumber = this.sanitizeCardNumber(cardNumber);

            if (!cardNumber) {
                return '';
            }

            var format = /(\d{1,4})/g;

            if (cardBrand && this.cardBrands[cardBrand]) {
                format = this.cardBrands[cardBrand].format;
            }

            var matches = cardNumber.match(format);

            if (matches) {
                return matches.join(' ').trim();
            }

            return cardNumber;
        },

        /**
         * Format expiry date as MM/YY
         *
         * @param {string} expiry - Expiry string
         * @return {string} Formatted expiry
         */
        formatExpiry: function(expiry) {
            expiry = expiry.replace(/\D/g, '');

            if (expiry.length >= 2) {
                return expiry.substring(0, 2) + (expiry.length > 2 ? '/' + expiry.substring(2, 4) : '');
            }

            return expiry;
        },

        /**
         * Sanitize card number (remove all non-digits)
         *
         * @param {string} cardNumber - Card number
         * @return {string} Sanitized card number
         */
        sanitizeCardNumber: function(cardNumber) {
            if (typeof cardNumber !== 'string') {
                return '';
            }
            return cardNumber.replace(/\D/g, '');
        },

        /**
         * Get card brand icon class
         *
         * @param {string} brand - Brand type
         * @return {string} Icon class
         */
        getCardIcon: function(brand) {
            var icons = {
                visa: 'megasoft-icon-visa',
                mastercard: 'megasoft-icon-mastercard',
                amex: 'megasoft-icon-amex',
                discover: 'megasoft-icon-discover',
                diners: 'megasoft-icon-diners'
            };

            return icons[brand] || 'megasoft-icon-card';
        }
    };

    // Export to global scope
    window.MegaSoftCardValidator = MegaSoftCardValidator;

})(window);
