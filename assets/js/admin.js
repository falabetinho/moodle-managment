jQuery(function($) {
    'use strict';

    // Save connection settings
    $('#save-settings').on('click', function(e) {
        e.preventDefault();

        var $form = $('#moodle-connection-form');
        var $resultDiv = $('#connection-result');

        $.ajax({
            type: 'POST',
            url: moodleManagement.ajaxurl,
            data: {
                action: 'moodle_save_settings',
                nonce: moodleManagement.nonce,
                base_url: $('#moodle_base_url').val(),
                username: $('#moodle_username').val(),
                token: $('#moodle_token').val()
            },
            beforeSend: function() {
                $('#save-settings').prop('disabled', true).text('Salvando...');
            },
            success: function(response) {
                $resultDiv
                    .removeClass('notice-error')
                    .addClass('notice notice-success')
                    .html('<p>' + response.data.message + '</p>')
                    .show();
            },
            error: function(response) {
                var errorMsg = response.responseJSON?.data?.message || 'Erro ao salvar configurações';
                $resultDiv
                    .removeClass('notice-success')
                    .addClass('notice notice-error')
                    .html('<p>' + errorMsg + '</p>')
                    .show();
            },
            complete: function() {
                $('#save-settings').prop('disabled', false).text('Salvar Configurações');
            }
        });
    });

    // Test connection
    $('#test-connection').on('click', function(e) {
        e.preventDefault();

        var $button = $(this);
        var $resultDiv = $('#connection-result');

        if (!$('#moodle_base_url').val() || !$('#moodle_username').val() || !$('#moodle_token').val()) {
            $resultDiv
                .removeClass('notice-success')
                .addClass('notice notice-error')
                .html('<p>Por favor, preencha todos os campos antes de testar.</p>')
                .show();
            return;
        }

        // First save the settings
        $.ajax({
            type: 'POST',
            url: moodleManagement.ajaxurl,
            data: {
                action: 'moodle_save_settings',
                nonce: moodleManagement.nonce,
                base_url: $('#moodle_base_url').val(),
                username: $('#moodle_username').val(),
                token: $('#moodle_token').val()
            },
            complete: function() {
                // Then test the connection
                $.ajax({
                    type: 'POST',
                    url: moodleManagement.ajaxurl,
                    data: {
                        action: 'moodle_test_connection',
                        nonce: moodleManagement.nonce
                    },
                    beforeSend: function() {
                        $button.prop('disabled', true).text('Testando...');
                    },
                    success: function(response) {
                        $resultDiv
                            .removeClass('notice-error')
                            .addClass('notice notice-success')
                            .html('<p>' + response.data.message + '</p>')
                            .show();
                    },
                    error: function(response) {
                        var errorMsg = response.responseJSON?.data?.message || 'Erro ao testar conexão';
                        $resultDiv
                            .removeClass('notice-success')
                            .addClass('notice notice-error')
                            .html('<p>' + errorMsg + '</p>')
                            .show();
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('Testar Conexão');
                    }
                });
            }
        });
    });

    // Sync categories
    $('#sync-categories').on('click', function(e) {
        e.preventDefault();

        var $button = $(this);
        var $resultDiv = $('#sync-categories-result');

        $.ajax({
            type: 'POST',
            url: moodleManagement.ajaxurl,
            data: {
                action: 'moodle_sync_categories',
                nonce: moodleManagement.nonce
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Sincronizando...');
            },
            success: function(response) {
                $resultDiv
                    .removeClass('notice-error')
                    .addClass('notice notice-success')
                    .html('<p>' + response.data.message + '</p>')
                    .show();

                // Reload page after 2 seconds
                setTimeout(function() {
                    location.reload();
                }, 2000);
            },
            error: function(response) {
                var errorMsg = response.responseJSON?.data?.message || 'Erro ao sincronizar categorias';
                $resultDiv
                    .removeClass('notice-success')
                    .addClass('notice notice-error')
                    .html('<p>' + errorMsg + '</p>')
                    .show();
            },
            complete: function() {
                $button.prop('disabled', false).text('Sincronizar Categorias do Moodle');
            }
        });
    });

    // Sync courses
    $('#sync-courses').on('click', function(e) {
        e.preventDefault();

        var $button = $(this);
        var $resultDiv = $('#sync-courses-result');

        $.ajax({
            type: 'POST',
            url: moodleManagement.ajaxurl,
            data: {
                action: 'moodle_sync_courses',
                nonce: moodleManagement.nonce
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Sincronizando...');
            },
            success: function(response) {
                $resultDiv
                    .removeClass('notice-error')
                    .addClass('notice notice-success')
                    .html('<p>' + response.data.message + '</p>')
                    .show();

                // Reload page after 2 seconds
                setTimeout(function() {
                    location.reload();
                }, 2000);
            },
            error: function(response) {
                var errorMsg = response.responseJSON?.data?.message || 'Erro ao sincronizar cursos';
                $resultDiv
                    .removeClass('notice-success')
                    .addClass('notice notice-error')
                    .html('<p>' + errorMsg + '</p>')
                    .show();
            },
            complete: function() {
                $button.prop('disabled', false).text('Sincronizar Cursos do Moodle');
            }
        });
    });

    // Sync enrollments
    $('#sync-enrollments').on('click', function(e) {
        e.preventDefault();

        var $button = $(this);
        var $resultDiv = $('#sync-enrollments-result');

        $.ajax({
            type: 'POST',
            url: moodleManagement.ajaxurl,
            data: {
                action: 'moodle_sync_enrollments',
                nonce: moodleManagement.nonce
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Sincronizando...');
            },
            success: function(response) {
                $resultDiv
                    .removeClass('notice-error')
                    .addClass('notice notice-success')
                    .html('<p>' + response.data.message + '</p>')
                    .show();

                // Reload page after 2 seconds
                setTimeout(function() {
                    location.reload();
                }, 2000);
            },
            error: function(response) {
                var errorMsg = response.responseJSON?.data?.message || 'Erro ao sincronizar enrollments';
                $resultDiv
                    .removeClass('notice-success')
                    .addClass('notice notice-error')
                    .html('<p>' + errorMsg + '</p>')
                    .show();
            },
            complete: function() {
                $button.prop('disabled', false).text('Sincronizar Enrollments do Moodle');
            }
        });
    });
});
