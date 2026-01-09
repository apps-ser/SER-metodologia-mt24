/**
 * JavaScript Admin - Metodologia Leitor-Apreciador
 *
 * @package MetodologiaLeitorApreciador
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // Confirmação de exclusão
        $('.button-link-delete').on('click', function () {
            return confirm(mlaAdmin.i18n.confirmDelete);
        });

        // Auto-dismiss notices
        setTimeout(function () {
            $('.notice.is-dismissible').fadeOut();
        }, 5000);

        // Análise por IA
        $('#mla-analyze-ia').on('click', function (e) {
            e.preventDefault();
            const textId = $(this).data('text-id');
            const $modal = $('#mla-ai-modal');
            const $content = $('#mla-ai-result-content');

            // Reset modal content
            $content.html('<p><em>' + mlaAdmin.i18n.processingAI + '</em></p><div class="mla-progress-bar"><div class="mla-progress-value" style="width: 100%;"></div></div>');

            // Open Modal
            $modal.dialog({
                modal: true,
                width: 800,
                height: 600,
                buttons: {
                    "Fechar": function () {
                        $(this).dialog("close");
                    }
                }
            });

            // AJAX call
            $.post(mlaAdmin.ajaxUrl, {
                action: 'mla_analyze_responses',
                text_id: textId,
                nonce: mlaAdmin.nonce
            }, function (response) {
                if (response.success) {
                    $content.html('<div class="mla-ai-result">' + formatMarkdown(response.data.content) + '</div>');
                } else {
                    $content.html('<div class="notice notice-error"><p>' + (response.data.message || 'Error') + '</p></div>');
                }
            }).fail(function () {
                $content.html('<div class="notice notice-error"><p>' + mlaAdmin.i18n.errorAI + '</p></div>');
            });
        });

        function formatMarkdown(text) {
            if (!text) return '';
            let html = text
                .replace(/^# (.*$)/gm, '<h1>$1</h1>')
                .replace(/^## (.*$)/gm, '<h2>$1</h2>')
                .replace(/^### (.*$)/gm, '<h3>$1</h3>')
                .replace(/^\* (.*$)/gm, '<li>$1</li>')
                .replace(/^- (.*$)/gm, '<li>$1</li>')
                .replace(/\n\n/g, '<br><br>');
            return html;
        }
    });

})(jQuery);
