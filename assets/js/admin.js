/**
 * MegaSoft v2 - Admin JavaScript
 *
 * Admin dashboard interactions and charts
 *
 * @package MegaSoft_Gateway_V2
 * @version 4.0.0
 */

(function($) {
    'use strict';

    var MegaSoftAdmin = {

        /**
         * Initialize
         */
        init: function() {
            this.initCharts();
            this.bindEvents();
        },

        /**
         * Initialize charts
         */
        initCharts: function() {
            // Transactions chart
            if ($('#megasoft-transactions-chart').length && typeof transactionsData !== 'undefined') {
                this.renderTransactionsChart(transactionsData);
            }

            // Approval rate chart
            if ($('#megasoft-approval-chart').length && typeof approvalData !== 'undefined') {
                this.renderApprovalChart(approvalData);
            }
        },

        /**
         * Render transactions chart
         */
        renderTransactionsChart: function(data) {
            var ctx = document.getElementById('megasoft-transactions-chart').getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(function(item) { return item.date; }),
                    datasets: [{
                        label: 'Transacciones',
                        data: data.map(function(item) { return item.count; }),
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        },

        /**
         * Render approval rate chart
         */
        renderApprovalChart: function(data) {
            var ctx = document.getElementById('megasoft-approval-chart').getContext('2d');

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Aprobadas', 'Rechazadas'],
                    datasets: [{
                        data: [data.approved, data.failed],
                        backgroundColor: ['#27ae60', '#e74c3c'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Export transactions
            $('#megasoft-export-transactions').on('click', function(e) {
                e.preventDefault();
                self.exportTransactions();
            });

            // Clear logs
            $('#megasoft-clear-logs').on('click', function(e) {
                e.preventDefault();
                self.clearLogs();
            });

            // View transaction details
            $('.megasoft-view-details').on('click', function(e) {
                e.preventDefault();
                var transactionId = $(this).data('transaction-id');
                self.viewTransactionDetails(transactionId);
            });
        },

        /**
         * Export transactions
         */
        exportTransactions: function() {
            if (!confirm(megasoftV2Admin.i18n.confirm_export || '¿Exportar todas las transacciones a CSV?')) {
                return;
            }

            var $button = $('#megasoft-export-transactions');
            $button.prop('disabled', true).text(megasoftV2Admin.i18n.processing);

            $.ajax({
                url: megasoftV2Admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'megasoft_v2_export_transactions',
                    nonce: megasoftV2Admin.nonce
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(data) {
                    // Create download link
                    var blob = new Blob([data], { type: 'text/csv' });
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = 'megasoft-transactions-' + new Date().toISOString().slice(0, 10) + '.csv';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);

                    $button.prop('disabled', false).text('Exportar CSV');
                },
                error: function() {
                    alert(megasoftV2Admin.i18n.error + ': No se pudo exportar');
                    $button.prop('disabled', false).text('Exportar CSV');
                }
            });
        },

        /**
         * Clear logs
         */
        clearLogs: function() {
            var days = prompt('¿Eliminar logs más antiguos de cuántos días?\n\nOpciones: 1, 7, 30, 60, 90 o 0 (todos)', '7');

            if (days === null) {
                return; // User cancelled
            }

            days = parseInt(days);
            if (isNaN(days) || days < 0) {
                alert('Por favor ingresa un número válido de días');
                return;
            }

            var confirmMessage = days === 0
                ? '¿Estás seguro de eliminar TODOS los logs?'
                : '¿Eliminar logs más antiguos de ' + days + ' días?';

            if (!confirm(confirmMessage)) {
                return;
            }

            var $button = $('#megasoft-clear-logs');
            $button.prop('disabled', true).text(megasoftV2Admin.i18n.processing);

            $.ajax({
                url: megasoftV2Admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'megasoft_v2_clear_logs',
                    nonce: megasoftV2Admin.nonce,
                    days: days  // 0 means delete all, handled correctly by server
                },
                success: function(response) {
                    if (response.success) {
                        alert(megasoftV2Admin.i18n.success + ': ' + response.data.message);
                        location.reload();
                    } else {
                        alert(megasoftV2Admin.i18n.error + ': ' + response.data);
                    }
                },
                error: function() {
                    alert(megasoftV2Admin.i18n.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Limpiar Logs BD');
                }
            });
        },

        /**
         * View transaction details
         */
        viewTransactionDetails: function(transactionId) {
            var self = this;
            var $sidebar = $('#megasoft-transaction-sidebar');
            var $loading = $sidebar.find('.megasoft-loading');
            var $content = $sidebar.find('.megasoft-details-content');

            // Show sidebar and loading
            $sidebar.addClass('is-open');
            $loading.show();
            $content.hide().html('');

            // Prevent body scroll
            $('body').css('overflow', 'hidden');

            // Fetch transaction details
            $.ajax({
                url: megasoftV2Admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'megasoft_v2_get_transaction_details',
                    nonce: megasoftV2Admin.nonce,
                    transaction_id: transactionId
                },
                success: function(response) {
                    if (response.success) {
                        self.renderTransactionDetails(response.data);
                        $loading.hide();
                        $content.show();
                    } else {
                        alert('Error: ' + (response.data || 'No se pudieron obtener los detalles'));
                        self.closeSidebar($sidebar);
                    }
                },
                error: function() {
                    alert('Error al cargar los detalles de la transacción');
                    self.closeSidebar($sidebar);
                }
            });

            // Close sidebar handlers
            $sidebar.find('.megasoft-sidebar-close, .megasoft-sidebar-overlay').off('click').on('click', function() {
                self.closeSidebar($sidebar);
            });

            // Close on Escape key
            $(document).off('keyup.megasoft-sidebar').on('keyup.megasoft-sidebar', function(e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    self.closeSidebar($sidebar);
                }
            });
        },

        /**
         * Close sidebar and restore body scroll
         */
        closeSidebar: function($sidebar) {
            $sidebar.removeClass('is-open');
            $('body').css('overflow', '');
            $(document).off('keyup.megasoft-sidebar');
        },

        /**
         * Render transaction details in sidebar
         */
        renderTransactionDetails: function(data) {
            var transaction = data.transaction;
            var order = data.order;
            var $content = $('#megasoft-transaction-sidebar .megasoft-details-content');

            var statusBadges = {
                'approved': '<span class="status-badge status-approved">Aprobada</span>',
                'failed': '<span class="status-badge status-failed">Rechazada</span>',
                'pending': '<span class="status-badge status-pending">Pendiente</span>',
                'declined': '<span class="status-badge status-failed">Declinada</span>'
            };

            var html = '<div class="megasoft-transaction-details">';

            // Order Info
            if (order && order.order_id) {
                html += '<div class="detail-section">';
                html += '<h3>Información del Pedido</h3>';
                html += '<table class="widefat">';
                html += '<tr><th>Pedido:</th><td><a href="' + order.edit_url + '" target="_blank">#' + order.order_number + '</a></td></tr>';
                html += '<tr><th>Estado:</th><td>' + order.order_status + '</td></tr>';
                html += '<tr><th>Cliente:</th><td>' + order.customer + '</td></tr>';
                html += '<tr><th>Email:</th><td>' + order.email + '</td></tr>';
                html += '<tr><th>Total:</th><td>' + order.currency + ' ' + order.total + '</td></tr>';
                html += '<tr><th>Fecha:</th><td>' + order.date + '</td></tr>';
                html += '</table>';
                html += '</div>';
            }

            // Transaction Info
            html += '<div class="detail-section">';
            html += '<h3>Información de la Transacción</h3>';
            html += '<table class="widefat">';
            html += '<tr><th>ID Transacción:</th><td>' + transaction.id + '</td></tr>';
            html += '<tr><th>Control:</th><td><code>' + transaction.control_number + '</code></td></tr>';
            html += '<tr><th>Autorización:</th><td><code>' + (transaction.authorization_code || '-') + '</code></td></tr>';
            html += '<tr><th>Tipo:</th><td>' + transaction.transaction_type + '</td></tr>';
            html += '<tr><th>Monto:</th><td>' + transaction.amount + '</td></tr>';
            html += '<tr><th>Tarjeta:</th><td>' + transaction.card_type + ' ' + transaction.card_last_four + '</td></tr>';
            html += '<tr><th>Estado:</th><td>' + (statusBadges[transaction.status_class] || transaction.status) + '</td></tr>';
            html += '<tr><th>Fecha:</th><td>' + transaction.created_at + '</td></tr>';
            if (transaction.error_message) {
                html += '<tr><th>Error:</th><td><span style="color: #dc3545;">' + transaction.error_message + '</span></td></tr>';
            }
            html += '</table>';
            html += '</div>';

            // Raw Data (Collapsible)
            html += '<div class="detail-section">';
            html += '<h3>Datos Técnicos</h3>';

            if (transaction.raw_request) {
                html += '<details>';
                html += '<summary style="cursor: pointer; font-weight: 600; margin-bottom: 10px;">Request XML</summary>';
                html += '<pre style="background: #f5f5f5; padding: 10px; overflow-x: auto; max-height: 300px;">' + this.escapeHtml(transaction.raw_request) + '</pre>';
                html += '</details>';
            }

            if (transaction.raw_response) {
                html += '<details style="margin-top: 10px;">';
                html += '<summary style="cursor: pointer; font-weight: 600; margin-bottom: 10px;">Response XML</summary>';
                html += '<pre style="background: #f5f5f5; padding: 10px; overflow-x: auto; max-height: 300px;">' + this.escapeHtml(transaction.raw_response) + '</pre>';
                html += '</details>';
            }

            html += '</div>';
            html += '</div>';

            $content.html(html);
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };

    // Initialize when ready
    $(document).ready(function() {
        MegaSoftAdmin.init();
    });

})(jQuery);
