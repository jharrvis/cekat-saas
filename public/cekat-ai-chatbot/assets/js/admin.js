/**
 * Cekat AI Chatbot - Admin JavaScript
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // Test Connection Button
        $('#cekat-test-connection').on('click', function () {
            var $button = $(this);
            var $status = $('#cekat-connection-status');
            var widgetId = $('#cekat_widget_id').val();

            if (!widgetId) {
                $status
                    .removeClass('success loading')
                    .addClass('error')
                    .text('Please enter a Widget ID first');
                return;
            }

            // Show loading state
            $button.prop('disabled', true);
            $status
                .removeClass('success error')
                .addClass('loading')
                .text('Testing...');

            // Make AJAX request
            $.ajax({
                url: cekatAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cekat_test_connection',
                    nonce: cekatAdmin.nonce,
                    widget_id: widgetId
                },
                success: function (response) {
                    if (response.success) {
                        $status
                            .removeClass('error loading')
                            .addClass('success')
                            .text('✓ ' + response.data.message + ' (' + response.data.widget_name + ')');
                    } else {
                        $status
                            .removeClass('success loading')
                            .addClass('error')
                            .text('✗ ' + response.data);
                    }
                },
                error: function () {
                    $status
                        .removeClass('success loading')
                        .addClass('error')
                        .text('✗ Connection failed. Please try again.');
                },
                complete: function () {
                    $button.prop('disabled', false);
                }
            });
        });

        // Auto-clear status when widget ID changes
        $('#cekat_widget_id').on('input', function () {
            $('#cekat-connection-status').text('').removeClass('success error loading');
        });
    });

})(jQuery);
