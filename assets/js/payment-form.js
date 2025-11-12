/**
 * MegaSoft v2 - Payment Form Handler
 *
 * Handles payment form interactions, validation, and submission
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 */

(function($) {
    'use strict';

    var MegaSoftPaymentForm = {

        /**
         * Initialize
         */
        init: function() {
            if (typeof MegaSoftCardValidator === 'undefined') {
                console.error('MegaSoft: Card validator library not loaded');
                return;
            }

            this.form = $('form.checkout');
            this.cardNumberField = $('#megasoft_v2_card_number');
            this.cardNameField = $('#megasoft_v2_card_name');
            this.cardExpiryField = $('#megasoft_v2_card_expiry');
            this.cardCvvField = $('#megasoft_v2_card_cvv');
            this.docTypeField = $('#megasoft_v2_doc_type');
            this.docNumberField = $('#megasoft_v2_doc_number');
            this.cardIcon = $('.megasoft-v2-card-icon');

            this.currentBrand = null;

            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Card number formatting and validation
            this.cardNumberField.on('input', function() {
                self.handleCardNumberInput($(this));
            });

            this.cardNumberField.on('blur', function() {
                self.validateCardNumber($(this));
            });

            // Expiry date formatting
            this.cardExpiryField.on('input', function() {
                self.handleExpiryInput($(this));
            });

            this.cardExpiryField.on('blur', function() {
                self.validateExpiry($(this));
            });

            // CVV validation
            this.cardCvvField.on('input', function() {
                self.handleCvvInput($(this));
            });

            this.cardCvvField.on('blur', function() {
                self.validateCvv($(this));
            });

            // Document number - only allow digits
            this.docNumberField.on('input', function() {
                var value = $(this).val().replace(/\D/g, '');
                $(this).val(value);
            });

            // Cardholder name - only allow letters and spaces
            this.cardNameField.on('input', function() {
                var value = $(this).val().replace(/[^a-zA-Z\s]/g, '');
                $(this).val(value.toUpperCase());
            });

            // Form validation on submit
            this.form.on('checkout_place_order_megasoft_v2', function() {
                return self.validateForm();
            });

            // Prevent accidental double submission
            this.form.on('submit', function() {
                if (self.isProcessing) {
                    return false;
                }
            });
        },

        /**
         * Handle card number input
         */
        handleCardNumberInput: function($field) {
            var value = $field.val();
            var sanitized = MegaSoftCardValidator.sanitizeCardNumber(value);

            // Detect brand
            var brand = MegaSoftCardValidator.detectCardBrand(sanitized);

            if (brand) {
                this.currentBrand = brand.type;
                this.updateCardIcon(brand.type);
            } else {
                this.currentBrand = null;
                this.updateCardIcon(null);
            }

            // Format card number
            var formatted = MegaSoftCardValidator.formatCardNumber(sanitized, this.currentBrand);
            $field.val(formatted);

            // Remove error class on input
            this.removeFieldError($field);
        },

        /**
         * Validate card number
         */
        validateCardNumber: function($field) {
            var value = $field.val();
            var result = MegaSoftCardValidator.validateCardNumber(value);

            if (!result.valid && value.length > 0) {
                this.showFieldError($field, megasoftV2Params.i18n.invalid_card);
                return false;
            } else {
                this.removeFieldError($field);
                return true;
            }
        },

        /**
         * Handle expiry input
         */
        handleExpiryInput: function($field) {
            var value = $field.val();
            var formatted = MegaSoftCardValidator.formatExpiry(value);
            $field.val(formatted);

            this.removeFieldError($field);
        },

        /**
         * Validate expiry
         */
        validateExpiry: function($field) {
            var value = $field.val();
            var parsed = MegaSoftCardValidator.parseExpiry(value);

            if (!parsed) {
                if (value.length > 0) {
                    this.showFieldError($field, megasoftV2Params.i18n.invalid_expiry);
                }
                return false;
            }

            var result = MegaSoftCardValidator.validateExpiry(parsed.month, parsed.year);

            if (!result.valid && value.length > 0) {
                this.showFieldError($field, result.message);
                return false;
            } else {
                this.removeFieldError($field);
                return true;
            }
        },

        /**
         * Handle CVV input
         */
        handleCvvInput: function($field) {
            var value = $field.val().replace(/\D/g, '');
            var maxLength = 3;

            if (this.currentBrand === 'amex') {
                maxLength = 4;
            }

            if (value.length > maxLength) {
                value = value.substring(0, maxLength);
            }

            $field.val(value);
            this.removeFieldError($field);
        },

        /**
         * Validate CVV
         */
        validateCvv: function($field) {
            var value = $field.val();
            var result = MegaSoftCardValidator.validateCVV(value, this.currentBrand);

            if (!result.valid && value.length > 0) {
                this.showFieldError($field, megasoftV2Params.i18n.invalid_cvv);
                return false;
            } else {
                this.removeFieldError($field);
                return true;
            }
        },

        /**
         * Update card icon
         */
        updateCardIcon: function(brand) {
            this.cardIcon.removeClass();
            this.cardIcon.addClass('megasoft-v2-card-icon');

            if (brand) {
                var iconClass = MegaSoftCardValidator.getCardIcon(brand);
                this.cardIcon.addClass(iconClass);
                this.cardIcon.addClass('active');
            }
        },

        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            var $parent = $field.closest('.form-row');
            $parent.addClass('woocommerce-invalid');
            $parent.removeClass('woocommerce-validated');

            // Remove existing error
            $parent.find('.megasoft-error').remove();

            // Add error message
            $field.after('<span class="megasoft-error">' + message + '</span>');
        },

        /**
         * Remove field error
         */
        removeFieldError: function($field) {
            var $parent = $field.closest('.form-row');
            $parent.removeClass('woocommerce-invalid');
            $parent.find('.megasoft-error').remove();
        },

        /**
         * Validate entire form
         */
        validateForm: function() {
            var valid = true;

            // Only validate if megasoft_v2 is selected
            var selectedPaymentMethod = $('input[name="payment_method"]:checked').val();
            if (selectedPaymentMethod !== 'megasoft_v2') {
                return true;
            }

            // Validate card number
            if (!this.validateCardNumber(this.cardNumberField)) {
                valid = false;
            }

            // Validate cardholder name
            var cardName = this.cardNameField.val().trim();
            if (cardName.length < 3) {
                this.showFieldError(this.cardNameField, 'El nombre del titular es requerido');
                valid = false;
            }

            // Validate expiry
            if (!this.validateExpiry(this.cardExpiryField)) {
                valid = false;
            }

            // Validate CVV
            if (!this.validateCvv(this.cardCvvField)) {
                valid = false;
            }

            // Validate document number
            var docNumber = this.docNumberField.val().trim();
            if (docNumber.length < 5) {
                this.showFieldError(this.docNumberField, megasoftV2Params.i18n.invalid_doc);
                valid = false;
            }

            if (!valid) {
                // Scroll to first error
                var $firstError = $('.woocommerce-invalid').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 500);
                }
            }

            return valid;
        },

        /**
         * Show processing overlay
         */
        showProcessing: function() {
            this.isProcessing = true;
            this.form.block({
                message: megasoftV2Params.i18n.processing,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        },

        /**
         * Hide processing overlay
         */
        hideProcessing: function() {
            this.isProcessing = false;
            this.form.unblock();
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        MegaSoftPaymentForm.init();
    });

    // Re-initialize on updated_checkout (for dynamic checkout updates)
    $(document.body).on('updated_checkout', function() {
        MegaSoftPaymentForm.init();
    });

})(jQuery);
