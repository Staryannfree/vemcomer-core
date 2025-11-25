/**
 * Admin Quick Toggle - Toggle rápido de featured para Menu Items e Restaurantes
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Toggle de Prato do Dia (Menu Items)
        $(document).on('change', '.vc-featured-toggle', function() {
            const $checkbox = $(this);
            const postId = $checkbox.data('post-id');
            const nonce = $checkbox.data('nonce');
            const $label = $checkbox.siblings('.vc-toggle-label');
            
            // Desabilitar durante a requisição
            $checkbox.prop('disabled', true);
            const originalValue = $checkbox.is(':checked');
            
            $.ajax({
                url: vcQuickToggle.restUrl + 'menu-items/' + postId + '/toggle-featured',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': vcQuickToggle.nonce
                },
                data: {
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Atualizar label
                        $label.text(response.featured ? '⭐' : '○');
                        // Mostrar notificação
                        showNotification(response.message, 'success');
                    } else {
                        // Reverter checkbox
                        $checkbox.prop('checked', !originalValue);
                        showNotification('Erro ao atualizar. Tente novamente.', 'error');
                    }
                },
                error: function(xhr) {
                    // Reverter checkbox
                    $checkbox.prop('checked', !originalValue);
                    const message = xhr.responseJSON?.message || 'Erro ao atualizar. Tente novamente.';
                    showNotification(message, 'error');
                },
                complete: function() {
                    $checkbox.prop('disabled', false);
                }
            });
        });

        // Toggle de Restaurante em Destaque
        $(document).on('change', '.vc-restaurant-featured-toggle', function() {
            const $checkbox = $(this);
            const postId = $checkbox.data('post-id');
            const nonce = $checkbox.data('nonce');
            const $label = $checkbox.siblings('.vc-toggle-label');
            
            // Desabilitar durante a requisição
            $checkbox.prop('disabled', true);
            const originalValue = $checkbox.is(':checked');
            
            $.ajax({
                url: vcQuickToggle.restUrl + 'restaurants/' + postId + '/toggle-featured',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': vcQuickToggle.nonce
                },
                data: {
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Atualizar label
                        $label.text(response.featured ? '⭐' : '○');
                        // Mostrar notificação
                        showNotification(response.message, 'success');
                    } else {
                        // Reverter checkbox
                        $checkbox.prop('checked', !originalValue);
                        showNotification('Erro ao atualizar. Tente novamente.', 'error');
                    }
                },
                error: function(xhr) {
                    // Reverter checkbox
                    $checkbox.prop('checked', !originalValue);
                    const message = xhr.responseJSON?.message || 'Erro ao atualizar. Tente novamente.';
                    showNotification(message, 'error');
                },
                complete: function() {
                    $checkbox.prop('disabled', false);
                }
            });
        });
    });

    /**
     * Mostra notificação no admin
     */
    function showNotification(message, type) {
        type = type || 'info';
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        // Remover notificações anteriores
        $('.vc-quick-toggle-notice').remove();
        $notice.addClass('vc-quick-toggle-notice');
        
        // Inserir após o título da página
        if ($('.wp-heading-inline').length) {
            $notice.insertAfter('.wp-heading-inline').parent();
        } else {
            $notice.prependTo('.wrap');
        }
        
        // Auto-dismiss após 3 segundos
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

})(jQuery);

