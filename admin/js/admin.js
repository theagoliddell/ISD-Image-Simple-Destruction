jQuery(document).ready(function($) {
    var isRunning = false;
    var totalCount = 0;
    var accumulatedCount = 0;
    var accumulatedBytes = 0;

    $('#isd-start-cleanup').on('click', function(e) {
        e.preventDefault();
        
        if (isRunning) return;

        if (!confirm(isd_params.messages.confirm)) {
            return;
        }

        // Inicializa UI de progresso
        isRunning = true;
        $('#isd-start-cleanup').prop('disabled', true);
        $('#isd-progress-wrapper').slideDown();
        $('#isd-progress-bar-fill').css('width', '0%');
        $('#isd-progress-text').text(isd_params.messages.scanning);
        $('#isd-progress-details').html('');
        $('#isd-progress-status-icon').removeClass('completed error').addClass('running').show();

        // Inicia escaneamento
        $.ajax({
            url: isd_params.ajax_url,
            type: 'POST',
            data: {
                action: 'isd_get_cleanup_stats',
                nonce: isd_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    totalCount = response.data.total_count;
                    accumulatedCount = 0;
                    accumulatedBytes = 0;

                    if (totalCount === 0) {
                        $('#isd-progress-text').text(isd_params.messages.no_images);
                        $('#isd-progress-status-icon').removeClass('running').hide();
                        $('#isd-start-cleanup').prop('disabled', false);
                        isRunning = false;
                        return;
                    }

                    $('#isd-progress-text').text(isd_params.messages.starting + ' ' + totalCount + ' ' + isd_params.messages.images + ' (' + isd_params.messages.est_size + ' ' + response.data.estimated_size + ')...');
                    
                    // Inicia a deleção recursiva por lotes
                    runCleanupStep(response.data.ids);
                } else {
                    showError(response.data.message || isd_params.messages.error);
                }
            },
            error: function() {
                showError(isd_params.messages.error);
            }
        });
    });

    function runCleanupStep(ids) {
        if (ids.length === 0) {
            finishCleanup();
            return;
        }

        $.ajax({
            url: isd_params.ajax_url,
            type: 'POST',
            data: {
                action: 'isd_run_cleanup_step',
                nonce: isd_params.nonce,
                ids: ids,
                accumulated_count: accumulatedCount,
                accumulated_bytes: accumulatedBytes
            },
            success: function(response) {
                if (response.success) {
                    accumulatedCount += response.data.deleted_count;
                    accumulatedBytes += response.data.bytes_saved;

                    var remaining = response.data.remaining_ids;
                    var processed = totalCount - remaining.length;
                    var percent = Math.round((processed / totalCount) * 100);

                    // Atualiza barra de progresso
                    $('#isd-progress-bar-fill').css('width', percent + '%');
                    $('#isd-progress-text').text(
                        isd_params.messages.deleting.replace('{percent}', percent)
                    );
                    
                    // Atualiza detalhes em tempo real
                    var sizeFormatted = formatBytes(accumulatedBytes);
                    $('#isd-progress-details').html(
                        '<strong>' + isd_params.messages.images_deleted + '</strong> ' + accumulatedCount + ' / ' + totalCount + '<br>' +
                        '<strong>' + isd_params.messages.space_freed + '</strong> ' + sizeFormatted
                    );

                    if (response.data.completed || remaining.length === 0) {
                        finishCleanup();
                    } else {
                        // Próximo lote
                        runCleanupStep(remaining);
                    }
                } else {
                    showError(response.data.message || isd_params.messages.error);
                }
            },
            error: function() {
                showError(isd_params.messages.error);
            }
        });
    }

    function finishCleanup() {
        $('#isd-progress-bar-fill').css('width', '100%');
        $('#isd-progress-text').text(isd_params.messages.completed);
        $('#isd-progress-status-icon').removeClass('running').addClass('completed');
        
        setTimeout(function() {
            // Recarrega a página para atualizar o histórico e estado das tabelas
            location.reload();
        }, 2000);
    }

    function showError(msg) {
        $('#isd-progress-text').text(isd_params.messages.error_prefix + ' ' + msg);
        $('#isd-progress-status-icon').removeClass('running').addClass('error');
        $('#isd-start-cleanup').prop('disabled', false);
        isRunning = false;
    }

    function formatBytes(bytes, decimals) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024,
            dm = decimals || 2,
            sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'],
            i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
});
