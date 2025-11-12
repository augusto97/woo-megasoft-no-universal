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
            if (!confirm(megasoftV2Admin.i18n.confirm_clear)) {
                return;
            }

            var $button = $('#megasoft-clear-logs');
            $button.prop('disabled', true).text(megasoftV2Admin.i18n.processing);

            $.ajax({
                url: megasoftV2Admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'megasoft_v2_clear_logs',
                    nonce: megasoftV2Admin.nonce
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
                    $button.prop('disabled', false).text('Limpiar Logs');
                }
            });
        },

        /**
         * View transaction details
         */
        viewTransactionDetails: function(transactionId) {
            // This could open a modal with full transaction details
            // For now, we'll just alert
            alert('Ver detalles de transacción #' + transactionId);
        }
    };

    // Initialize when ready
    $(document).ready(function() {
        MegaSoftAdmin.init();
    });

})(jQuery);
