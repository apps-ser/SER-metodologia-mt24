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

        const $projectFilter = $('#mla-filter-project');
        const $textFilter = $('#mla-filter-text');
        const $aiAnalyzeBtn = $('#mla-analyze-ia');
        const $viewHistoryBtn = $('#mla-view-history');

        // Toggle AI buttons based on text selection
        function toggleAIButtons() {
            const textId = $textFilter.val();
            if (textId) {
                $aiAnalyzeBtn.prop('disabled', false).attr('data-text-id', textId);

                // Construir URL do histórico corretamente
                const baseUrl = mlaAdmin.adminUrl + 'admin.php?page=mla-responses&view=analyses';
                const newUrl = baseUrl + '&text_id=' + encodeURIComponent(textId);
                $viewHistoryBtn.attr('href', newUrl);

                $viewHistoryBtn.removeClass('disabled').css({ 'pointer-events': 'auto', 'opacity': '1' });
            } else {
                $aiAnalyzeBtn.prop('disabled', true).attr('data-text-id', '');
                $viewHistoryBtn.attr('href', '#');
                $viewHistoryBtn.addClass('disabled').css({ 'pointer-events': 'none', 'opacity': '0.5' });
            }
        }

        // Initial check
        toggleAIButtons();

        // Listener for Project change to update Text filter
        $projectFilter.on('change', function () {
            const projectId = $(this).val();

            // Show loading state in text filter
            $textFilter.prop('disabled', true).find('option:not(:first)').remove();
            $textFilter.find('option:first').text('Carregando...');

            $.post(mlaAdmin.ajaxUrl, {
                action: 'mla_get_texts_by_project',
                project_id: projectId,
                nonce: mlaAdmin.nonce
            }, function (response) {
                if (response.success) {
                    $textFilter.find('option:first').text('Todos os textos');
                    $.each(response.data.texts, function (id, title) {
                        $textFilter.append($('<option>', { value: id, text: title }));
                    });
                } else {
                    $textFilter.find('option:first').text('Erro ao carregar');
                }
            }).fail(function () {
                $textFilter.find('option:first').text('Erro ao carregar');
            }).always(function () {
                $textFilter.prop('disabled', false);
                toggleAIButtons();
            });
        });

        // Listener for Text change
        $textFilter.on('change', toggleAIButtons);

        // Análise por IA
        $('#mla-analyze-ia').on('click', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const textId = $btn.data('text-id');
            const $modal = $('#mla-ai-modal');
            const $content = $('#mla-ai-result-content');

            if (!textId) return;

            // Disable button and show loading state
            $btn.prop('disabled', true).addClass('updating-message');

            // Open Modal immediately to show "Processing"
            $modal.dialog({
                modal: true,
                width: 800,
                height: 600,
                buttons: {
                    "Fechar": function () {
                        $(this).dialog("close");
                    }
                },
                close: function () {
                    $btn.prop('disabled', false).removeClass('updating-message');
                }
            });

            // Reset modal content with detailed processing message
            $content.html('<div class="mla-ai-processing">' +
                '<p><strong>' + mlaAdmin.i18n.processingAI + '</strong></p>' +
                '<p>' + mlaAdmin.i18n.dontCloseTip + '</p>' +
                '<div class="mla-progress-bar"><div class="mla-progress-value" style="width: 100%;"></div></div>' +
                '</div>');

            // AJAX call
            $.post(mlaAdmin.ajaxUrl, {
                action: 'mla_analyze_responses',
                text_id: textId,
                nonce: mlaAdmin.nonce
            }, function (response) {
                if (response.success) {
                    const historyUrl = mlaAdmin.adminUrl + 'admin.php?page=mla-responses&view=analyses&text_id=' + encodeURIComponent(textId);
                    $content.html('<div class="mla-ai-result">' +
                        formatMarkdown(response.data.content) +
                        '</div>' +
                        '<div class="mla-modal-actions">' +
                        '<a href="' + historyUrl + '" class="button button-primary">' +
                        '<span class="dashicons dashicons-backup" style="margin-top:4px; margin-right:5px;"></span>' +
                        'Ver no Histórico Completo</a>' +
                        '</div>');
                } else {
                    const errorMsg = response.data && response.data.message ? response.data.message : 'Erro desconhecido';
                    $content.html('<div class="notice notice-error"><p><strong>Erro na Análise:</strong> ' + errorMsg + '</p></div>');
                }
            }).fail(function (xhr) {
                let failMsg = mlaAdmin.i18n.errorAI;
                if (xhr.status === 504) failMsg += ' (Timeout da requisição)';
                $content.html('<div class="notice notice-error"><p>' + failMsg + '</p></div>');
            }).always(function () {
                $btn.prop('disabled', false).removeClass('updating-message');
            });
        });

        function formatMarkdown(text) {
            if (!text) return '';

            // Basic Markdown Parser
            let html = text
                .replace(/^# (.*$)/gm, '<h1>$1</h1>')
                .replace(/^## (.*$)/gm, '<h2>$1</h2>')
                .replace(/^### (.*$)/gm, '<h3>$1</h3>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/^\* (.*$)/gm, '<li>$1</li>')
                .replace(/^- (.*$)/gm, '<li>$1</li>')
                .replace(/\n\n/g, '</p><p>')
                .replace(/\n/g, '<br>');

            // Wrap lists
            html = html.replace(/(<li>.*<\/li>)/gms, '<ul>$1</ul>');

            return '<p>' + html + '</p>';
        }
    });

})(jQuery);
