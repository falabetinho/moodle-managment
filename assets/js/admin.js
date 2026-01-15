jQuery(function($) {
    'use strict';

    // Save connection settings
    $('#moodle-connection-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $resultDiv = $('#connection-result');

        $.ajax({
            type: 'POST',
            url: moodleManagement.ajaxurl,
            data: {
                action: 'moodle_save_settings',
                nonce: $form.find('input[name="nonce"]').val(),
                base_url: $('#moodle_base_url').val(),
                username: $('#moodle_username').val(),
                token: $('#moodle_token').val()
            },
            beforeSend: function() {
                $form.find('button[type="submit"]').prop('disabled', true).text('Salvando...');
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
                $form.find('button[type="submit"]').prop('disabled', false).text('Salvar Configurações');
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
                nonce: $('#moodle-connection-form').find('input[name="nonce"]').val(),
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

    // Sync enrol methods
    $('#sync-enrol-methods').on('click', function(e) {
        e.preventDefault();

        var $button = $(this);
        var $resultDiv = $('#sync-enrol-result');
        var courseId = $('#enrol-course-select').val();
        if (!courseId || courseId === '0') {
            $resultDiv
                .removeClass('notice-success')
                .addClass('notice notice-error')
                .html('<p>Selecione um curso para sincronizar.</p>')
                .show();
            return;
        }

        $.ajax({
            type: 'POST',
            url: moodleManagement.ajaxurl,
            data: {
                action: 'moodle_sync_enrol_methods',
                nonce: moodleManagement.nonce,
                course_id: courseId
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
                var errorMsg = response.responseJSON?.data?.message || 'Erro ao sincronizar métodos de enrol';
                $resultDiv
                    .removeClass('notice-success')
                    .addClass('notice notice-error')
                    .html('<p>' + errorMsg + '</p>')
                    .show();
            },
            complete: function() {
                $button.prop('disabled', false).text('Sincronizar Métodos de Enrol do Moodle');
            }
        });
    });

    // Sync all enrol methods from all courses
    $('#sync-all-enrol-methods').on('click', function(e) {
        e.preventDefault();

        var $button = $(this);
        var $resultDiv = $('#sync-all-enrol-result');

        if (!confirm('Isso irá sincronizar os métodos de enrol de todos os cursos. Pode levar alguns minutos. Deseja continuar?')) {
            return;
        }

        $.ajax({
            type: 'POST',
            url: moodleManagement.ajaxurl,
            data: {
                action: 'moodle_sync_all_enrol_methods',
                nonce: moodleManagement.nonce
            },
            timeout: 300000, // 5 minutes timeout
            beforeSend: function() {
                $button.prop('disabled', true).text('Sincronizando... Isso pode levar alguns minutos.');
                $resultDiv
                    .removeClass('notice-success notice-error')
                    .addClass('notice notice-info')
                    .html('<p>Sincronizando métodos de enrol de todos os cursos...</p>')
                    .show();
            },
            success: function(response) {
                $resultDiv
                    .removeClass('notice-error notice-info')
                    .addClass('notice notice-success')
                    .html('<p>' + response.data.message + '</p>')
                    .show();

                // Reload page after 3 seconds
                setTimeout(function() {
                    location.reload();
                }, 3000);
            },
            error: function(response) {
                var errorMsg = response.responseJSON?.data?.message || 'Erro ao sincronizar métodos de enrol';
                $resultDiv
                    .removeClass('notice-success notice-info')
                    .addClass('notice notice-error')
                    .html('<p>' + errorMsg + '</p>')
                    .show();
            },
            complete: function() {
                $button.prop('disabled', false).text('Sincronizar Todos os Enrol de Todos os Cursos');
            }
        });
    });
});
